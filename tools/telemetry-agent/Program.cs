using System.ComponentModel;
using System.Diagnostics;
using System.Net.Http.Headers;
using System.Net.Http.Json;
using System.Security.Principal;
using System.Text.Json;
using System.Text.Json.Serialization;
using LibreHardwareMonitor.Hardware;
using LibreHardwareMonitor.PawnIo;

namespace PcTelemetryAgent;

internal static class Program
{
    internal const string Version = "1.3.1";

    public static async Task<int> Main(string[] args)
    {
        Console.OutputEncoding = System.Text.Encoding.UTF8;
        Console.Title = "PC Telemetry Agent";

        var baseDirectory = AppContext.BaseDirectory;
        var configPath = Argument(args, "--config")
            ?? Path.Combine(baseDirectory, "telemetry-agent.json");

        if (!File.Exists(configPath))
        {
            Console.Error.WriteLine($"Configuração não encontrada: {configPath}");
            Console.Error.WriteLine("Execute configure-telemetry.cmd na pasta do projeto.");
            return 2;
        }

        AgentConfig? config;
        try
        {
            config = JsonSerializer.Deserialize<AgentConfig>(
                await File.ReadAllTextAsync(configPath),
                JsonOptions.Default);
        }
        catch (Exception exception)
        {
            Console.Error.WriteLine($"Configuração inválida: {exception.Message}");
            return 2;
        }

        var endpoints = new List<Uri>();
        var configuredEndpoints = new[] { config?.Endpoint }
            .Concat(config?.Endpoints ?? []);

        foreach (var configuredEndpoint in configuredEndpoints)
        {
            if (Uri.TryCreate(configuredEndpoint, UriKind.Absolute, out var endpoint)
                && !endpoints.Contains(endpoint))
            {
                endpoints.Add(endpoint);
            }
        }

        if (config is null
            || endpoints.Count == 0
            || string.IsNullOrWhiteSpace(config.Token)
            || string.IsNullOrWhiteSpace(config.AgentId))
        {
            Console.Error.WriteLine("Informe ao menos um endpoint e um token válidos em telemetry-agent.json.");
            return 2;
        }

        if (!IsAdministrator())
        {
            Console.WriteLine("A temperatura da CPU exige acesso administrativo.");
            Console.WriteLine("Solicitando permissao do Windows...");

            try
            {
                RelaunchAsAdministrator(args);
                return 0;
            }
            catch (Win32Exception exception) when (exception.NativeErrorCode == 1223)
            {
                Console.Error.WriteLine("Permissao administrativa cancelada. A telemetria nao foi iniciada.");
                return 5;
            }
            catch (Exception exception)
            {
                Console.Error.WriteLine($"Nao foi possivel iniciar o agente como administrador: {exception.Message}");
                return 5;
            }
        }

        if (!PawnIo.IsInstalled)
        {
            Console.ForegroundColor = ConsoleColor.Yellow;
            Console.WriteLine("PawnIO nao esta instalado. A Integridade de memoria do Windows pode impedir a leitura da temperatura da CPU.");
            Console.WriteLine("Instalador oficial: https://github.com/namazso/PawnIO.Setup/releases/latest");
            Console.ResetColor();
        }

        var computer = new Computer
        {
            IsCpuEnabled = true,
            IsGpuEnabled = true,
            IsMotherboardEnabled = true,
            IsControllerEnabled = true,
            IsMemoryEnabled = true,
            IsStorageEnabled = true,
        };
        computer.Open();

        var collector = new MetricsCollector(computer, config.AgentId);

        if (args.Contains("--list-sensors", StringComparer.OrdinalIgnoreCase))
        {
            collector.PrintSensors();
            return 0;
        }

        using var client = new HttpClient { Timeout = TimeSpan.FromSeconds(8) };
        client.DefaultRequestHeaders.Authorization = new AuthenticationHeaderValue("Bearer", config.Token);
        client.DefaultRequestHeaders.UserAgent.ParseAdd($"PC-Telemetry-Agent/{Version}");

        using var cancellation = new CancellationTokenSource();
        Console.CancelKeyPress += (_, eventArgs) =>
        {
            eventArgs.Cancel = true;
            cancellation.Cancel();
        };

        var once = args.Contains("--once", StringComparer.OrdinalIgnoreCase);
        var interval = TimeSpan.FromSeconds(Math.Clamp(config.IntervalSeconds, 5, 300));

        Console.WriteLine($"PC Telemetry Agent {Version}");
        Console.WriteLine($"Destinos: {string.Join(", ", endpoints)}");
        Console.WriteLine("Pressione Ctrl+C para encerrar.");

        do
        {
            try
            {
                var payload = collector.Collect();
                foreach (var endpoint in endpoints)
                {
                    try
                    {
                        using var response = await client.PostAsJsonAsync(endpoint, payload, JsonOptions.Default, cancellation.Token);
                        var status = response.IsSuccessStatusCode ? "enviado" : $"HTTP {(int)response.StatusCode}";
                        Console.WriteLine($"[{DateTime.Now:HH:mm:ss}] {status} para {endpoint.Host} | CPU {Display(payload.CpuTemp, "°C")} | GPU {Display(payload.GpuTemp, "°C")}");
                    }
                    catch (OperationCanceledException) when (cancellation.IsCancellationRequested)
                    {
                        throw;
                    }
                    catch (Exception exception)
                    {
                        Console.Error.WriteLine($"[{DateTime.Now:HH:mm:ss}] falha em {endpoint.Host}: {exception.Message}");
                    }
                }
            }
            catch (OperationCanceledException) when (cancellation.IsCancellationRequested)
            {
                break;
            }
            catch (Exception exception)
            {
                Console.Error.WriteLine($"[{DateTime.Now:HH:mm:ss}] falha: {exception.Message}");
            }

            if (once) break;

            try
            {
                await Task.Delay(interval, cancellation.Token);
            }
            catch (OperationCanceledException)
            {
                break;
            }
        } while (!cancellation.IsCancellationRequested);

        computer.Close();
        return 0;
    }

    private static string? Argument(string[] args, string name)
    {
        var index = Array.FindIndex(args, value => value.Equals(name, StringComparison.OrdinalIgnoreCase));
        return index >= 0 && index + 1 < args.Length ? args[index + 1] : null;
    }

    private static bool IsAdministrator()
    {
        using var identity = WindowsIdentity.GetCurrent();
        return new WindowsPrincipal(identity).IsInRole(WindowsBuiltInRole.Administrator);
    }

    private static void RelaunchAsAdministrator(string[] args)
    {
        var executable = Environment.ProcessPath
            ?? throw new InvalidOperationException("Caminho do executavel nao identificado.");
        var startInfo = new ProcessStartInfo(executable)
        {
            UseShellExecute = true,
            Verb = "runas",
            WorkingDirectory = AppContext.BaseDirectory,
        };

        foreach (var argument in args)
        {
            startInfo.ArgumentList.Add(argument);
        }

        _ = Process.Start(startInfo)
            ?? throw new InvalidOperationException("O Windows nao iniciou o processo elevado.");
    }

    private static string Display(float? value, string suffix) => value is null ? "—" : $"{value:0.0}{suffix}";
}

internal sealed class MetricsCollector(Computer computer, string agentId)
{
    public TelemetryPayload Collect()
    {
        var sensors = Sensors();
        var cpu = sensors.Where(sensor => sensor.Hardware.HardwareType is HardwareType.Cpu).ToArray();
        var gpu = sensors.Where(sensor => sensor.Hardware.HardwareType is HardwareType.GpuNvidia or HardwareType.GpuAmd or HardwareType.GpuIntel).ToArray();
        var memory = sensors.Where(sensor => sensor.Hardware.HardwareType is HardwareType.Memory).ToArray();

        return new TelemetryPayload(
            agentId,
            DateTimeOffset.UtcNow,
            Temperature(cpu, "CPU Package") ?? Maximum(cpu, SensorType.Temperature),
            Temperature(gpu, "GPU Core") ?? Maximum(gpu, SensorType.Temperature),
            Load(cpu, "CPU Total") ?? Maximum(cpu, SensorType.Load),
            Load(gpu, "GPU Core") ?? Maximum(gpu, SensorType.Load),
            Load(memory, "Memory") ?? Maximum(memory, SensorType.Load),
            MainDiskUsage(),
            Find(sensors, SensorType.Fan, "pump"),
            Find(sensors, SensorType.Temperature, "coolant", "liquid", "water"),
            Math.Max(0, Environment.TickCount64 / 1000),
            Program.Version);
    }

    public void PrintSensors()
    {
        foreach (var sensor in Sensors().OrderBy(sensor => sensor.Hardware.Name).ThenBy(sensor => sensor.SensorType))
        {
            Console.WriteLine($"{sensor.Hardware.HardwareType,-14} | {sensor.Hardware.Name,-30} | {sensor.SensorType,-12} | {sensor.Name,-30} | {sensor.Value}");
        }
    }

    private ISensor[] Sensors()
    {
        computer.Accept(new UpdateVisitor());
        return computer.Hardware
            .SelectMany(AllHardware)
            .SelectMany(hardware => hardware.Sensors)
            .Where(sensor => sensor.Value is not null)
            .ToArray();
    }

    private static IEnumerable<IHardware> AllHardware(IHardware hardware)
    {
        yield return hardware;
        foreach (var subHardware in hardware.SubHardware.SelectMany(AllHardware)) yield return subHardware;
    }

    private static float? Temperature(IEnumerable<ISensor> sensors, string name) =>
        sensors.FirstOrDefault(sensor => sensor.SensorType == SensorType.Temperature && sensor.Name.Contains(name, StringComparison.OrdinalIgnoreCase))?.Value;

    private static float? Load(IEnumerable<ISensor> sensors, string name) =>
        sensors.FirstOrDefault(sensor => sensor.SensorType == SensorType.Load && sensor.Name.Contains(name, StringComparison.OrdinalIgnoreCase))?.Value;

    private static float? Maximum(IEnumerable<ISensor> sensors, SensorType type) =>
        sensors.Where(sensor => sensor.SensorType == type).Select(sensor => sensor.Value).OfType<float>().DefaultIfEmpty().Max() is var value && value > 0 ? value : null;

    private static float? Find(IEnumerable<ISensor> sensors, SensorType type, params string[] names) =>
        sensors.FirstOrDefault(sensor => sensor.SensorType == type && names.Any(name => sensor.Name.Contains(name, StringComparison.OrdinalIgnoreCase)))?.Value;

    private static float? MainDiskUsage()
    {
        try
        {
            var systemRoot = Path.GetPathRoot(Environment.SystemDirectory);
            var drive = DriveInfo.GetDrives().FirstOrDefault(candidate =>
                candidate.IsReady
                && candidate.DriveType == DriveType.Fixed
                && string.Equals(candidate.RootDirectory.FullName, systemRoot, StringComparison.OrdinalIgnoreCase));

            if (drive is null || drive.TotalSize <= 0) return null;

            return (float)Math.Round((drive.TotalSize - drive.AvailableFreeSpace) * 100d / drive.TotalSize, 1);
        }
        catch
        {
            return null;
        }
    }
}

internal sealed class UpdateVisitor : IVisitor
{
    public void VisitComputer(IComputer computer) => computer.Traverse(this);
    public void VisitHardware(IHardware hardware)
    {
        hardware.Update();
        foreach (var subHardware in hardware.SubHardware) subHardware.Accept(this);
    }
    public void VisitSensor(ISensor sensor) { }
    public void VisitParameter(IParameter parameter) { }
}

internal sealed record AgentConfig(
    [property: JsonPropertyName("endpoint")] string? Endpoint,
    [property: JsonPropertyName("endpoints")] string[]? Endpoints,
    [property: JsonPropertyName("token")] string Token,
    [property: JsonPropertyName("agent_id")] string AgentId,
    [property: JsonPropertyName("interval_seconds")] int IntervalSeconds = 10);

internal sealed record TelemetryPayload(
    [property: JsonPropertyName("agent_id")] string AgentId,
    [property: JsonPropertyName("collected_at")] DateTimeOffset CollectedAt,
    [property: JsonPropertyName("cpu_temp")] float? CpuTemp,
    [property: JsonPropertyName("gpu_temp")] float? GpuTemp,
    [property: JsonPropertyName("cpu_load")] float? CpuLoad,
    [property: JsonPropertyName("gpu_load")] float? GpuLoad,
    [property: JsonPropertyName("memory_usage")] float? MemoryUsage,
    [property: JsonPropertyName("disk_usage")] float? DiskUsage,
    [property: JsonPropertyName("pump_rpm")] float? PumpRpm,
    [property: JsonPropertyName("coolant_temp")] float? CoolantTemp,
    [property: JsonPropertyName("uptime_seconds")] long? UptimeSeconds,
    [property: JsonPropertyName("agent_version")] string AgentVersion);

internal static class JsonOptions
{
    public static readonly JsonSerializerOptions Default = new(JsonSerializerDefaults.Web)
    {
        WriteIndented = true,
        DefaultIgnoreCondition = JsonIgnoreCondition.WhenWritingNull,
        PropertyNameCaseInsensitive = true,
    };
}
