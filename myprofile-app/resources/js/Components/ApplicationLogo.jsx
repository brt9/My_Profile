export default function ApplicationLogo(props) {
    return (
        <svg {...props} viewBox="0 0 64 64" role="img" aria-label="Pedro Felipe">
            <rect width="64" height="64" rx="19" fill="currentColor" />
            <text x="32" y="39" textAnchor="middle" fill="white" fontFamily="Inter, sans-serif" fontSize="23" fontWeight="800">PF</text>
        </svg>
    );
}
