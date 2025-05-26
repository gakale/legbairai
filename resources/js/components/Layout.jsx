// resources/js/components/Layout.jsx
import React, { useState, useEffect } from 'react';
import { Link, Outlet } from 'react-router-dom'; // Outlet est utilisé pour rendre les composants de route enfants

const Navbar = () => {
    const [scrolled, setScrolled] = useState(false);

    useEffect(() => {
        const handleScroll = () => {
            setScrolled(window.scrollY > 50);
        };
        window.addEventListener('scroll', handleScroll);
        return () => window.removeEventListener('scroll', handleScroll);
    }, []);

    return (
        <nav className={`fixed top-0 w-full z-[1000] transition-all duration-300 ease-in-out px-4 sm:px-8 py-4 ${scrolled ? 'bg-[rgba(15,15,26,0.95)] shadow-gb-strong' : 'bg-[rgba(15,15,26,0.8)]'}`} style={{ backdropFilter: 'blur(20px)' }}>
            <div className="max-w-[1400px] mx-auto flex justify-between items-center">
                <Link to="/" className="text-2xl font-black flex items-center gap-2">
                    <span className="text-3xl animate-pulse">🎙️</span>
                    <span className="bg-gb-gradient-1 bg-clip-text text-transparent">
                        Le gbairai
                    </span>
                </Link>
                <div className="hidden md:flex gap-8 items-center">
                    {/* Liens de navigation à remplacer par ceux de React Router si nécessaire */}
                    <a href="/#features" className="text-gb-white relative font-medium hover:after:w-full after:content-[''] after:absolute after:bottom-[-5px] after:left-0 after:w-0 after:h-[2px] after:bg-gb-accent after:transition-all after:duration-300">Fonctionnalités</a>
                    <a href="/#creators" className="text-gb-white relative font-medium hover:after:w-full after:content-[''] after:absolute after:bottom-[-5px] after:left-0 after:w-0 after:h-[2px] after:bg-gb-accent after:transition-all after:duration-300">Créateurs</a>
                    {/* ... autres liens ... */}
                    <Link to="/login" className="btn-secondary">Se connecter</Link>
                    <Link to="/register" className="btn-primary">Commencer</Link>
                </div>
                <div className="md:hidden">
                    {/* Bouton burger pour mobile à implémenter */}
                    <button className="text-gb-white text-2xl">☰</button>
                </div>
            </div>
        </nav>
    );
};

// Définition des classes de boutons pour correspondre à votre CSS
// Idéalement, vous auriez un composant Button réutilisable.
// Pour l'instant, je vais les mettre ici pour la Navbar.
// Assurez-vous que ces classes correspondent aux styles que vous voulez de votre charte.
// Si votre CSS original est importé globalement, ces classes pourraient déjà exister.
// Sinon, vous devrez les recréer avec Tailwind ou les importer.
// Pour l'instant, on va styler les Link comme des boutons directement.

const Footer = () => {
    return (
        <footer className="py-8 text-center border-t border-[rgba(255,255,255,0.1)]">
            <div className="max-w-[1200px] mx-auto">
                <p className="text-gb-light-gray text-sm">© {new Date().getFullYear()} Le gbairai. Tous droits réservés.</p>
                <div className="flex gap-4 justify-center mt-4">
                    {/* Liens sociaux */}
                    <a href="#" className="w-10 h-10 rounded-full bg-[rgba(255,255,255,0.1)] flex items-center justify-center text-gb-white transition-all duration-300 hover:bg-gb-primary hover:scale-110">📧</a>
                    <a href="#" className="w-10 h-10 rounded-full bg-[rgba(255,255,255,0.1)] flex items-center justify-center text-gb-white transition-all duration-300 hover:bg-gb-primary hover:scale-110">🐦</a>
                    {/* ... autres liens sociaux ... */}
                </div>
            </div>
        </footer>
    );
};


const Layout = () => {
    return (
        <div className="bg-gb-dark text-gb-white min-h-screen flex flex-col">
            <Navbar />
            <main className="flex-grow pt-[80px]"> {/* pt pour compenser la hauteur de la navbar fixe */}
                <Outlet /> {/* Les composants de page de React Router seront rendus ici */}
            </main>
            <Footer />
        </div>
    );
};

export default Layout;