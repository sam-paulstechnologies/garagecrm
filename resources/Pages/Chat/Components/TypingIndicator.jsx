export default function TypingIndicator() {
    return (
        <div className="flex items-center space-x-2 text-gray-400 text-sm px-3 py-1">
            <span className="animate-pulse">●</span>
            <span className="animate-pulse delay-150">●</span>
            <span className="animate-pulse delay-300">●</span>
            <span>Typing…</span>
        </div>
    );
}
