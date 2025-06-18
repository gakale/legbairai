import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import Button from '../common/Button';
import { SpaceType } from '../../utils/enums';
import { createSpace } from '../../services/spaceService';

const CreateSpaceForm = () => {
    const [title, setTitle] = useState('');
    const [description, setDescription] = useState('');
    const [spaceType, setSpaceType] = useState(SpaceType.PUBLIC_FREE);
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState('');
    const navigate = useNavigate();

    const handleSubmit = async (e) => {
        e.preventDefault();
        setIsLoading(true);
        setError('');

        const spaceData = {
            title,
            description,
            type: spaceType,
            is_recording_enabled_by_host: false, // Default value for now
            // Other fields like ticket_price, currency can be added later
        };

        try {
            const response = await createSpace(spaceData);
            setIsLoading(false);
            // Assuming the API returns the created space object with its ID
            // and data is nested under response.data.data (common for Laravel API resources)
            if (response.data && response.data.data && response.data.data.id) {
                navigate(`/spaces/${response.data.data.id}`);
            } else {
                // Fallback if ID is not where expected, or navigate to a general success page
                console.warn("Created space ID not found in expected location in response:", response);
                navigate('/'); // Or a generic success page
            }
        } catch (err) {
            setIsLoading(false);
            console.error("Erreur lors de la création du Space:", err);
            const errorMessage = err.response?.data?.message || "Erreur lors de la création du Space. Veuillez réessayer.";
            setError(errorMessage);
        }
    };

    return (
        <div className="max-w-2xl mx-auto bg-gb-dark-light p-8 rounded-lg shadow-lg">
            <h2 className="text-2xl font-bold text-gb-white mb-6 text-center">
                Créer un nouveau Space
            </h2>
            <form onSubmit={handleSubmit} className="space-y-6">
                {error && (
                    <div className="bg-red-700 text-white p-3 rounded-md text-sm">
                        {error}
                    </div>
                )}
                <div>
                    <label htmlFor="title" className="block text-sm font-medium text-gb-gray mb-1">
                        Titre du Space
                    </label>
                    <input
                        type="text"
                        id="title"
                        value={title}
                        onChange={(e) => setTitle(e.target.value)}
                        required
                        className="w-full px-4 py-2 bg-gb-dark border border-gb-gray-dark rounded-md text-gb-white focus:ring-gb-teal focus:border-gb-teal"
                        placeholder="Mon incroyable Space"
                        disabled={isLoading}
                    />
                </div>

                <div>
                    <label htmlFor="description" className="block text-sm font-medium text-gb-gray mb-1">
                        Description
                    </label>
                    <textarea
                        id="description"
                        value={description}
                        onChange={(e) => setDescription(e.target.value)}
                        rows="4"
                        className="w-full px-4 py-2 bg-gb-dark border border-gb-gray-dark rounded-md text-gb-white focus:ring-gb-teal focus:border-gb-teal"
                        placeholder="Une brève description de ce qui rend votre Space unique..."
                        disabled={isLoading}
                    ></textarea>
                </div>

                <div>
                    <label htmlFor="spaceType" className="block text-sm font-medium text-gb-gray mb-1">
                        Type de Space
                    </label>
                    <select
                        id="spaceType"
                        value={spaceType}
                        onChange={(e) => setSpaceType(e.target.value)}
                        className="w-full px-4 py-2 bg-gb-dark border border-gb-gray-dark rounded-md text-gb-white focus:ring-gb-teal focus:border-gb-teal"
                        disabled={isLoading}
                    >
                        <option value={SpaceType.PUBLIC_FREE}>Public (Gratuit)</option>
                        <option value={SpaceType.PUBLIC_PAID}>Public (Payant)</option>
                        <option value={SpaceType.PRIVATE_INVITE}>Privé (Sur Invitation)</option>
                        <option value={SpaceType.PRIVATE_SUBSCRIBER}>Privé (Abonnés)</option>
                    </select>
                </div>

                <div className="pt-2">
                    <Button type="submit" variant="primary" className="w-full" disabled={isLoading}>
                        {isLoading ? 'Création en cours...' : 'Créer le Space'}
                    </Button>
                </div>
            </form>
        </div>
    );
};

export default CreateSpaceForm;
