import { Head, Link } from '@inertiajs/react';

export default function Welcome({ auth, laravelVersion, phpVersion }) {
    return (
        <>
            <Head title="Welcome" />
            <div className="bg-gradient-to-b from-blue-500 to-indigo-600 text-white min-h-screen flex items-center justify-center">
                <div className="container mx-auto px-4 text-center">
                    <div className="py-12">
                        <h1 className="text-4xl font-bold mb-4">Welcome to Shithead</h1>
                        <p className="text-lg mb-8">
                            {auth.user
                                ? `Welcome back, ${auth.user.name}!`
                                : 'Welcome to Shithead! Log in or register to get started.'}
                        </p>

                        {auth.user ? (
                            <Link
                                href={route('dashboard')}
                                className="bg-white text-blue-600 font-semibold py-3 px-6 rounded-lg shadow-lg hover:bg-gray-100 transition"
                            >
                                Go to Lobby 
                            </Link>
                        ) : (
                            <div className="flex justify-center space-x-4">
                                <Link
                                    href={route('login')}
                                    className="bg-white text-blue-600 font-semibold py-3 px-6 rounded-lg shadow-lg hover:bg-gray-100 transition"
                                >
                                    Log In
                                </Link>
                                <Link
                                    href={route('register')}
                                    className="bg-indigo-500 text-white font-semibold py-3 px-6 rounded-lg shadow-lg hover:bg-indigo-400 transition"
                                >
                                    Register
                                </Link>
                            </div>
                        )}
                    </div>

                    <footer className="mt-12 text-sm text-white/80">
                        Laravel v{laravelVersion} (PHP v{phpVersion})
                    </footer>
                </div>
            </div>
        </>
    );
}
