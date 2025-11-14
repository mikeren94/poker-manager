function LoadingSpinner({message}) {
    return (
        <div className="flex items-center justify-center mt-4">
            <div className="animate-spin rounded-full h-6 w-6 border-t-2 border-b-2 border-blue-500"></div>
            <span className="ml-2 text-blue-500">{message}</span>
        </div>
    )
}

export default LoadingSpinner;