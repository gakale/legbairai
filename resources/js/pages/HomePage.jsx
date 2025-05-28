// resources/js/pages/HomePage.jsx
import React, { useState, useEffect, useCallback } from 'react';
import HeroSection from './HomePageSections/HeroSection'; // Importer
import SpaceService from '../services/SpaceService';
import SpaceCard from '../components/spaces/SpaceCard';
import Button from '../components/common/Button';

const HomePage = () => {
    const [spaces, setSpaces] = useState([]);
    const [displayCount, setDisplayCount] = useState(6); // Nouvelle constante
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState('');
    const [currentPage, setCurrentPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [isLoadingMore, setIsLoadingMore] = useState(false);
    const [hasMore, setHasMore] = useState(false);

    const fetchSpaces = useCallback(async (pageToLoad) => {
        if (pageToLoad === 1) setIsLoading(true);
        else setIsLoadingMore(true);
        setError('');

        try {
            const response = await SpaceService.getSpacesFeed(pageToLoad);
            const newSpaces = response.data.data;
            if (pageToLoad === 1) {
                setSpaces(newSpaces);
            } else {
                setSpaces(prevSpaces => [...prevSpaces, ...newSpaces]);
            }
            setCurrentPage(response.data.meta?.current_page || pageToLoad);
            setLastPage(response.data.meta?.last_page || 1);
        } catch (err) {
            console.error("Erreur chargement des spaces:", err);
            setError("Impossible de charger les Spaces pour le moment.");
        } finally {
            if (pageToLoad === 1) setIsLoading(false);
            else setIsLoadingMore(false);
        }
    }, []);

    const loadSpaces = useCallback(async () => {
        try {
            const data = await fetchSpaces(currentPage);
            setSpaces(prev => [...prev, ...data.data]);
            setHasMore(data.current_page < data.last_page);
        } catch (error) {
            console.error('Failed to load spaces:', error);
        }
    }, [currentPage]);

    useEffect(() => {
        fetchSpaces(1); 
    }, []);

    const handleLoadMore = () => {
        if (currentPage < lastPage && !isLoadingMore) {
            fetchSpaces(currentPage + 1);
        }
    };

    if (isLoading) {
        return <div className="text-center py-20 text-gb-light-gray">Chargement des Spaces...</div>;
    }

    if (error) {
        return <div className="text-center py-20 text-red-400">{error}</div>;
    }

    return (
        <>
            <HeroSection />

            <section className="py-12 md:py-20 bg-gb-dark">
                <div className="container mx-auto px-4">
                    <h2 className="text-3xl md:text-4xl font-bold text-gb-white text-center mb-10 md:mb-12">
                        Découvrez les <span className="bg-gb-gradient-1 bg-clip-text text-transparent">Spaces</span>
                    </h2>

                    {spaces.length === 0 && !isLoading && (
                        <p className="text-center text-gb-light-gray">Aucun Space disponible pour le moment.</p>
                    )}

                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
                        {spaces.slice(0, displayCount).map((space) => (
                            <SpaceCard key={space.id} space={space} />
                        ))}
                    </div>

                    {hasMore && !isLoadingMore && (
                        <div className="text-center mt-12">
                            <Button onClick={loadSpaces} variant="primary">
                                Charger plus de Spaces
                            </Button>
                        </div>
                    )}
                    {isLoadingMore && <p className="text-center mt-8 text-gb-light-gray">Chargement...</p>}
                </div>
            </section>
        </>
    );
};

export default HomePage;