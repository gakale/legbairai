// resources/js/pages/CreateSpacePage.jsx
import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import CreateSpaceModal from '../components/spaces/CreateSpaceModal';

const CreateSpacePage = () => {
    const navigate = useNavigate();
    const [showModal, setShowModal] = useState(true);

    const handleSpaceCreated = (newSpace) => {
        // Rediriger vers le space créé
        navigate(`/spaces/${newSpace.id}`);
    };

    const handleClose = () => {
        // Retourner à la page précédente ou à l'accueil
        navigate(-1);
    };

    return (
        <div className="min-h-screen bg-gb-dark">
            <div className="max-w-4xl mx-auto px-4 py-8">
                <div className="text-center mb-8">
                    <h1 className="text-3xl font-bold text-gb-white mb-4">
                        Créer un nouveau Space
                    </h1>
                    <p className="text-gb-light-gray">
                        Lancez votre propre space audio et connectez-vous avec votre audience
                    </p>
                </div>

                {/* Le modal s'affiche automatiquement */}
                <CreateSpaceModal 
                    isOpen={showModal}
                    onClose={handleClose}
                    onSpaceCreated={handleSpaceCreated}
                />
            </div>
        </div>
    );
};

export default CreateSpacePage;
