export default function MessageBubble({ message }) {
    const isOutbound = message.direction === "out";
    const isAI = message.is_ai === true;

    return (
        <div
            className={`flex ${
                isOutbound ? "justify-end" : "justify-start"
            } w-full`}
        >
            <div
                className={`max-w-lg rounded-xl px-4 py-2 shadow-sm ${
                    isOutbound
                        ? "bg-blue-600 text-white"
                        : isAI
                        ? "bg-gray-200 text-gray-800 border border-gray-300 italic"
                        : "bg-white border border-gray-300"
                }`}
            >
                <div className="whitespace-pre-line text-sm">{message.body}</div>

                <div className="text-[10px] mt-1 opacity-70 text-right">
                    {message.created_at}
                </div>
            </div>
        </div>
    );
}
