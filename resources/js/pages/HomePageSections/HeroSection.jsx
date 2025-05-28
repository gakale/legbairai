// resources/js/pages/HomePageSections/HeroSection.jsx
import React from 'react';
import Button from '../../components/common/Button'; // Import the Button component

// Vous pouvez créer des composants plus petits pour le PhoneMockup, Stats, etc.
// Pour l'instant, on intègre tout ici pour commencer.

const PhoneMockup = () => (
    <div className="w-[280px] sm:w-[300px] h-[560px] sm:h-[600px] bg-gb-dark-lighter rounded-4xl p-2.5 shadow-gb-strong relative overflow-hidden">
        <div className="w-full h-full bg-gradient-to-b from-gb-dark to-gb-dark-lighter rounded-[30px] overflow-hidden relative">
            <div className="p-6 sm:p-8 h-full flex flex-col">
                <div className="text-center mb-8">
                    <span className="inline-block bg-gb-accent text-gb-white px-4 py-1 rounded-full text-xs sm:text-sm font-semibold mb-4 animate-pulse">● LIVE</span>
                    <h3 className="text-lg sm:text-xl mb-1 text-gb-white">Innovation & Tech Talk</h3>
                    <p className="text-gb-light-gray text-sm">avec @TechGuru</p>
                </div>
                <div className="grid grid-cols-3 gap-4 mb-auto">
                    {/* Speakers - Répétez pour chaque speaker */}
                    {['🎤', '👨‍💻', '🎧', '🎵', '💡', '🚀'].map((emoji, index) => (
                        <div key={index} className="text-center">
                            <div className={`w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-gb-gradient-1 mx-auto mb-2 relative flex items-center justify-center text-2xl sm:text-3xl ${index === 0 ? 'speaker-speaking-animation' : ''}`}>
                                {emoji}
                            </div>
                            <p className="text-xs text-gb-light-gray">{['Alex', 'Marie', 'Tom', 'Lisa', 'Sam', 'Jules'][index]}</p>
                        </div>
                    ))}
                </div>
                <div className="flex justify-center gap-3 sm:gap-4 p-3 sm:p-4 bg-[rgba(255,255,255,0.05)] rounded-2xl">
                    {['🎤', '✋', '💬', '❤️'].map((emoji, index) => (
                        <button key={index} className="w-10 h-10 sm:w-12 sm:h-12 rounded-full border-none bg-gb-dark-lighter text-gb-white text-lg sm:text-xl cursor-pointer transition-all duration-300 hover:bg-gb-primary hover:scale-110">
                            {emoji}
                        </button>
                    ))}
                </div>
            </div>
        </div>
    </div>
);

const HeroSection = () => {
    const waveBars = Array.from({ length: 16 }, (_, i) => {
        // Hauteurs un peu aléatoires pour l'effet
        const heights = ['h-[40%]', 'h-[60%]', 'h-[80%]', 'h-[45%]', 'h-[70%]', 'h-[55%]', 'h-[85%]', 'h-[65%]', 'h-[50%]', 'h-[75%]', 'h-[40%]', 'h-[90%]', 'h-[60%]', 'h-[45%]', 'h-[80%]', 'h-[55%]'];
        return (
            <div
                key={i}
                className={`flex-1 bg-gradient-to-t from-gb-primary-light to-gb-accent opacity-30 rounded-t-md wave-bar-animation`}
                style={{ '--i': i, height: heights[i % heights.length] }} // L'animation delay sera via CSS
            />
        );
    });


    return (
        <section className="min-h-screen flex items-center relative overflow-hidden py-24 px-4 sm:px-8"> {/* pt-24 pour la navbar */}
            {/* Fond avec cercles et vagues */}
            <div className="absolute top-0 left-0 w-full h-full z-[-1]">
                {/* Cercles Flottants */}
                <div className="absolute w-full h-full">
                    <div className="absolute w-[300px] h-[300px] bg-gb-gradient-1 rounded-full opacity-10 circle-float-animation top-[10%] left-[10%] animation-delay-0"></div>
                    <div className="absolute w-[200px] h-[200px] bg-gb-gradient-2 rounded-full opacity-10 circle-float-animation top-[60%] right-[10%] animation-delay-2s"></div>
                    <div className="absolute w-[150px] h-[150px] bg-gb-gradient-1 rounded-full opacity-10 circle-float-animation top-[30%] right-[30%] animation-delay-4s"></div>
                </div>
                {/* Conteneur des Vagues */}
                <div className="absolute bottom-0 left-0 w-full h-[150px] sm:h-[200px] flex items-end gap-0.5 sm:gap-1">
                    {waveBars}
                </div>
            </div>

            {/* Contenu Principal du Hero */}
            <div className="max-w-[1400px] mx-auto grid md:grid-cols-2 gap-8 sm:gap-16 items-center z-[1]">
                {/* Colonne Texte */}
                <div className="hero-text-animation text-center md:text-left">
                    <h1 className="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-black leading-tight mb-6">
                        La révolution <span className="bg-gb-gradient-1 bg-clip-text text-transparent animate-glow-text">audio sociale</span> est arrivée
                    </h1>
                    <p className="text-lg sm:text-xl text-gb-light-gray opacity-90 mb-8">
                        Créez, partagez et monétisez vos conversations audio en temps réel. Rejoignez des milliers de créateurs qui réinventent l'interaction sociale.
                    </p>
                    <div className="flex flex-col sm:flex-row gap-4 mb-12 justify-center md:justify-start">
                        <Button to="/register" variant="primary" className="text-base sm:text-lg">Créer mon Space</Button>
                        <Button to="/explore" variant="secondary" className="text-base sm:text-lg">Explorer les Spaces</Button>
                    </div>
                    <div className="flex gap-8 sm:gap-12 justify-center md:justify-start">
                        <div className="text-center">
                            <span className="block text-2xl sm:text-3xl font-extrabold text-gb-primary-light">50K+</span>
                            <span className="text-sm text-gb-light-gray">Créateurs actifs</span>
                        </div>
                        <div className="text-center">
                            <span className="block text-2xl sm:text-3xl font-extrabold text-gb-primary-light">2M+</span>
                            <span className="text-sm text-gb-light-gray">Auditeurs mensuels</span>
                        </div>
                        <div className="text-center">
                            <span className="block text-2xl sm:text-3xl font-extrabold text-gb-primary-light">10K+</span>
                            <span className="text-sm text-gb-light-gray">Spaces quotidiens</span>
                        </div>
                    </div>
                </div>

                {/* Colonne Visuelle (Mockup Téléphone) */}
                <div className="relative flex justify-center items-center hero-visual-animation">
                    <div className="absolute w-full h-full pointer-events-none">
                        <div className="absolute text-4xl emoji-float-animation top-[20%] left-[-50px] animation-delay-0">🎙️</div>
                        <div className="absolute text-4xl emoji-float-animation top-[50%] right-[-50px] animation-delay-1s">💬</div>
                        <div className="absolute text-4xl emoji-float-animation bottom-[20%] left-[-30px] animation-delay-2s">🎧</div>
                    </div>
                    <PhoneMockup />
                </div>
            </div>
        </section>
    );
};

export default HeroSection;