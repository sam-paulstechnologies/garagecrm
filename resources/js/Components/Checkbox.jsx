export default function Checkbox({ className = '', ...props }) {
    return (
        <input
            {...props}
            type="checkbox"
            className={
                'rounded border-[#0D1B3D]/25 text-[#FF6A00] shadow-sm focus:ring-[#FF6A00] ' +
                className
            }
        />
    );
}
