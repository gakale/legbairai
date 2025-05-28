// resources/js/pages/HomePage.jsx
import React from 'react';
import HeroSection from './HomePageSections/HeroSection'; // Importer

const HomePage = () => {
    console.log("[HomePage.jsx] - Le composant HomePage est en cours de rendu.");
    return (
        <>
            <HeroSection />
            {/* Vous ajouterez les autres sections (Features, CTA) ici plus tard */}
            {/* <FeaturesSection /> */}
            {/* <CtaSection /> */}
        </>
    );
};

export default HomePage;