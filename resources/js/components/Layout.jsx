// resources/js/components/Layout.jsx
import React, { useState, useEffect } from 'react';
import { Link, Outlet, useNavigate } from 'react-router-dom';
import Button from './common/Button';
import { useAuth } from '../contexts/AuthContext';

const Navbar = () => {
    const [scrolled, setScrolled] = useState(false);
    const { isAuthenticated, currentUser, logout } = useAuth();
    const navigate = useNavigate();

    useEffect(() => {
        const handleScroll = () => {
            setScrolled(window.scrollY > 50);
        };
        window.addEventListener('scroll', handleScroll);
        return () => window.removeEventListener('scroll', handleScroll);
    }, []);

    const handleLogout = async () => {
        await logout();
        navigate('/');
    };

    return (
        <nav className={`fixed top-0 w-full z-[1000] transition-all duration-300 ease-in-out px-4 sm:px-8 py-4 ${scrolled ? 'bg-[rgba(15,15,26,0.95)] shadow-gb-strong' : 'bg-[rgba(15,15,26,0.8)]'}`} style={{ backdropFilter: 'blur(20px)' }}>
            <div className="max-w-[1400px] mx-auto flex justify-between items-center">
                <Link to="/" className="text-2xl font-black flex items-center gap-2">
                    <span className="text-3xl animate-pulse">🎙️</span>
                    <span className="bg-gb-gradient-1 bg-clip-text text-transparent">
                        Le gbairai
                    </span>
                </Link>
                <div className="hidden md:flex gap-4 sm:gap-8 items-center">
                    <a href="/#features" className="text-gb-white relative font-medium hover:after:w-full after:content-[''] after:absolute after:bottom-[-5px] after:left-0 after:w-0 after:h-[2px] after:bg-gb-accent after:transition-all after:duration-300">Fonctionnalités</a>
                    <a href="/#creators" className="text-gb-white relative font-medium hover:after:w-full after:content-[''] after:absolute after:bottom-[-5px] after:left-0 after:w-0 after:h-[2px] after:bg-gb-accent after:transition-all after:duration-300">Créateurs</a>
                    {isAuthenticated ? (
                        <>
                            {currentUser && (
                                <Link to={`/profile/${currentUser.id}`} className="text-gb-white font-medium hover:text-gb-primary-light">
                                    {currentUser.username}
                                </Link>
                            )}
                            <Button onClick={handleLogout} variant="secondary" className="py-2 px-6 text-sm">
                                Déconnexion
                            </Button>
                        </>
                    ) : (
                        <>
                            <Button to="/login" variant="secondary" className="py-2 px-6 text-sm">
                                Se connecter
                            </Button>
                            <Button to="/register" variant="primary" className="py-2 px-6 text-sm">
                                Commencer
                            </Button>
                        </>
                    )}
                </div>
                <div className="md:hidden">
                    <button className="text-gb-white text-2xl">☰</button>
                </div>
            </div>
        </nav>
    );
};

const Footer = () => {
    return (
        <footer className="py-8 text-center border-t border-[rgba(255,255,255,0.1)]">
            <div className="max-w-[1200px] mx-auto">
                <p className="text-gb-light-gray text-sm">© {new Date().getFullYear()} Le gbairai. Tous droits réservés.</p>
                <div className="flex gap-4 justify-center mt-4">
                    <a href="#" className="w-10 h-10 rounded-full bg-[rgba(255,255,255,0.1)] flex items-center justify-center text-gb-white transition-all duration-300 hover:bg-gb-primary hover:scale-110">📧</a>
                    <a href="#" className="w-10 h-10 rounded-full bg-[rgba(255,255,255,0.1)] flex items-center justify-center text-gb-white transition-all duration-300 hover:bg-gb-primary hover:scale-110">🐦</a>
                </div>
            </div>
        </footer>
    );
};

const Layout = () => {
    return (
        <div className="bg-gb-dark text-gb-white min-h-screen flex flex-col">
            <Navbar />
            <main className="flex-grow pt-[80px]">
                <Outlet />
            </main>
            <Footer />
        </div>
    );
};

export default Layout;