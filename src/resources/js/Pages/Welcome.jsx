import ApplicationLogo from '@/Components/ApplicationLogo';
import { Head, Link } from '@inertiajs/react';

export default function Welcome({ auth, laravelVersion, phpVersion }) {
    return (
        <>
            <Head title="Welcome" />
            <div className="bg-gray-50 text-black dark:bg-black dark:text-white min-h-screen flex flex-col justify-center items-center px-6">
                <div className="max-w-4xl w-full text-center">
                    <div className="flex justify-center mb-6">
                        <ApplicationLogo className="h-16 w-auto text-blue-500 dark:text-blue-400" />
                    </div>

                    <h1 className="text-4xl font-bold text-black dark:text-white mb-4">
                        Welcome to StackTrackr
                    </h1>
                    <p className="text-lg text-black/60 dark:text-white/60 mb-8">
                        Log your hands, review your play, and start building your poker history.
                        <br/><br/>
                        This application is in active development, currently only PokerStars cash games are supported.
                        More features and sites will be added over time.
                    </p>

                    <div className="flex justify-center gap-4">
                        {auth.user ? (
                            <Link
                                href={route('dashboard')}
                                className="px-6 py-3 rounded-md bg-blue-500 text-white hover:bg-blue-600 transition"
                            >
                                Go to Dashboard
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href={route('login')}
                                    className="px-6 py-3 rounded-md bg-blue-500 text-white hover:bg-blue-600 transition"
                                >
                                    Log In
                                </Link>
                                <Link
                                    href={route('register')}
                                    className="px-6 py-3 rounded-md border border-blue-500 text-blue-500 hover:bg-blue-500 hover:text-white transition"
                                >
                                    Register
                                </Link>
                            </>
                        )}
                    </div>

                    <footer className="mt-12 text-sm text-black/50 dark:text-white/50">
                        Laravel v{laravelVersion} · PHP v{phpVersion}
                    </footer>
                </div>
            </div>
        </>
    );
}