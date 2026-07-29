export default function SecondaryButton({
    type = 'button',
    className = '',
    disabled,
    children,
    ...props
}) {
    return (
        <button
            {...props}
            type={type}
            className={
                `inline-flex items-center rounded-xl border border-[#0D1B3D]/20 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-[#0D1B3D] shadow-sm transition duration-150 ease-in-out hover:bg-[#0D1B3D]/5 focus:outline-none focus:ring-2 focus:ring-[#FF6A00] focus:ring-offset-2 disabled:opacity-25 ${
                    disabled && 'opacity-25'
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
