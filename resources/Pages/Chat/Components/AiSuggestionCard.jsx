export default function AiSuggestionCard({ text, confidence, onSelect }) {
    return (
        <button
            onClick={onSelect}
            className="
                bg-gray-100 border border-gray-300 text-gray-700 
                px-3 py-2 rounded-lg text-sm shadow-sm hover:bg-gray-200 
                min-w-[160px] text-left
            "
        >
            <div className="font-semibold text-xs text-blue-600">
                AI ({Math.round(confidence * 100)}%)
            </div>
            {text}
        </button>
    );
}
