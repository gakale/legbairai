import React, { useState, useEffect, useCallback } from 'react';
import HeroSection from './HomePageSections/HeroSection';
import { getSpaces } from '../services/spaceService';
import SpaceCard from '../components/spaces/SpaceCard';
import Button from '../components/common/Button';
import { Link } from 'react-router-dom';

const HomePage = () => {
    const [recentSpaces, setRecentSpaces] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState('');

    const fetchRecentSpaces = useCallback(async () => {
        setIsLoading(true);
        setError('');
        try {
            const response = await getSpaces(1, 6);
            setRecentSpaces(response.data);
        } catch (err) {
            console.error("Erreur chargement des spaces:", err);
            setError("Impossible de charger les Spaces pour le moment.");
        }
        setIsLoading(false);
    }, []);

    useEffect(() => {
        fetchRecentSpaces();
    }, [fetchRecentSpaces]);

    if (isLoading) {
        return (
            <>
                <HeroSection />
                <div className="text-center py-20 text-gb-light-gray">Chargement des Spaces...</div>
            </>
        );
    }

    if (error) {
        return (
            <>
                <HeroSection />
                <div className="text-center py-20 text-red-400">{error}</div>
            </>
        );
    }

    return (
        <>
            <HeroSection />

            <section className="py-12 md:py-20 bg-gb-dark">
                <div className="container mx-auto px-4">
                    <h2 className="text-3xl md:text-4xl font-bold text-gb-white text-center mb-10 md:mb-12">
                        Découvrez les <span className="bg-gb-gradient-1 bg-clip-text text-transparent">Spaces Récents</span>
                    </h2>

                    {recentSpaces.length === 0 && (
                        <p className="text-center text-gb-light-gray">Aucun Space disponible pour le moment.</p>
                    )}

                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
                        {recentSpaces.map((space) => (
                            <SpaceCard key={space.id} space={space} />
                        ))}
                    </div>

                    {recentSpaces.length > 0 && (
                        <div className="text-center mt-12">
                            <Link to="/spaces">
                                <Button variant="primary">
                                    Voir tous les Spaces
                                </Button>
                            </Link>
                        </div>
                    )}
                </div>
            </section>
        </>
    );
};

export default HomePage;