// resources/js/components/spaces/CreateSpaceModal.jsx
import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import Button from '../common/Button';
import { createSpace } from '../../services/spaceService';

const CreateSpaceModal = ({ isOpen, onClose, onSpaceCreated }) => {
    const [formData, setFormData] = useState({
        title: '',
        description: '',
        type: 'public_free',
        scheduled_at: '',
        is_recording_enabled_by_host: false,
        max_participants: 50
    });
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState('');
    const [startImmediately, setStartImmediately] = useState(true);
    const navigate = useNavigate();

    const handleInputChange = (e) => {
        const { name, value, type, checked } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: type === 'checkbox' ? checked : value
        }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setIsLoading(true);
        setError('');

        try {
            // Préparer les données selon si c'est programmé ou immédiat
            const spaceData = {
                ...formData,
                // Si on démarre immédiatement, ne pas envoyer scheduled_at
                ...(startImmediately ? { scheduled_at: null } : {})
            };

            const response = await createSpace(spaceData);
            const createdSpace = response.data.data || response.data;
            
            // Notifier le parent du nouveau space
            if (onSpaceCreated) {
                onSpaceCreated(createdSpace);
            }
            
            // Rediriger vers le space créé pour tester WebRTC
            navigate(`/spaces/${createdSpace.id}`);
            onClose();
        } catch (err) {
            console.error('Erreur création space:', err);
            const errorMessage = err.response?.data?.message || 
                                err.response?.data?.errors || 
                                'Erreur lors de la création du space';
            setError(typeof errorMessage === 'object' ? JSON.stringify(errorMessage) : errorMessage);
        } finally {
            setIsLoading(false);
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div className="bg-gb-dark-light rounded-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div className="p-6">
                    {/* En-tête */}
                    <div className="flex items-center justify-between mb-6">
                        <h2 className="text-2xl font-bold text-gb-white">
                            Créer un nouveau Space
                        </h2>
                        <button
                            onClick={onClose}
                            className="text-gb-light-gray hover:text-gb-white"
                        >
                            ✕
                        </button>
                    </div>

                    {/* Formulaire */}
                    <form onSubmit={handleSubmit} className="space-y-6">
                        {error && (
                            <div className="bg-red-900 border border-red-700 text-red-200 p-3 rounded-md">
                                {error}
                            </div>
                        )}

                        {/* Titre */}
                        <div>
                            <label className="block text-sm font-medium text-gb-gray mb-2">
                                Titre du Space *
                            </label>
                            <input
                                type="text"
                                name="title"
                                value={formData.title}
                                onChange={handleInputChange}
                                required
                                placeholder="Ex: Discussion sur le WebRTC..."
                                className="w-full px-4 py-2 bg-gb-dark border border-gb-gray-dark rounded-md text-gb-white placeholder-gb-light-gray focus:outline-none focus:border-gb-purple"
                            />
                        </div>

                        {/* Description */}
                        <div>
                            <label className="block text-sm font-medium text-gb-gray mb-2">
                                Description
                            </label>
                            <textarea
                                name="description"
                                value={formData.description}
                                onChange={handleInputChange}
                                rows={3}
                                placeholder="Décrivez brièvement le sujet de votre space..."
                                className="w-full px-4 py-2 bg-gb-dark border border-gb-gray-dark rounded-md text-gb-white placeholder-gb-light-gray focus:outline-none focus:border-gb-purple resize-none"
                            />
                        </div>

                        {/* Type de space */}
                        <div>
                            <label className="block text-sm font-medium text-gb-gray mb-2">
                                Type de Space
                            </label>
                            <select
                                name="type"
                                value={formData.type}
                                onChange={handleInputChange}
                                className="w-full px-4 py-2 bg-gb-dark border border-gb-gray-dark rounded-md text-gb-white focus:outline-none focus:border-gb-purple"
                            >
                                <option value="public_free">Public - Gratuit</option>
                                <option value="public_paid">Public - Payant</option>
                                <option value="private">Privé</option>
                            </select>
                        </div>

                        {/* Planification */}
                        <div>
                            <label className="block text-sm font-medium text-gb-gray mb-3">
                                Quand démarrer ?
                            </label>
                            <div className="space-y-3">
                                <label className="flex items-center">
                                    <input
                                        type="radio"
                                        checked={startImmediately}
                                        onChange={() => setStartImmediately(true)}
                                        className="mr-2 text-gb-purple"
                                    />
                                    <span className="text-gb-white">Démarrer maintenant (idéal pour tester WebRTC)</span>
                                </label>
                                <label className="flex items-center">
                                    <input
                                        type="radio"
                                        checked={!startImmediately}
                                        onChange={() => setStartImmediately(false)}
                                        className="mr-2 text-gb-purple"
                                    />
                                    <span className="text-gb-white">Programmer pour plus tard</span>
                                </label>
                            </div>

                            {!startImmediately && (
                                <div className="mt-3">
                                    <input
                                        type="datetime-local"
                                        name="scheduled_at"
                                        value={formData.scheduled_at}
                                        onChange={handleInputChange}
                                        min={new Date().toISOString().slice(0, 16)}
                                        className="w-full px-4 py-2 bg-gb-dark border border-gb-gray-dark rounded-md text-gb-white focus:outline-none focus:border-gb-purple"
                                    />
                                </div>
                            )}
                        </div>

                        {/* Options avancées */}
                        <div className="space-y-4">
                            <h3 className="text-lg font-medium text-gb-white">Options</h3>
                            
                            <label className="flex items-center">
                                <input
                                    type="checkbox"
                                    name="is_recording_enabled_by_host"
                                    checked={formData.is_recording_enabled_by_host}
                                    onChange={handleInputChange}
                                    className="mr-2"
                                />
                                <span className="text-gb-white">Autoriser l'enregistrement</span>
                            </label>

                            <div>
                                <label className="block text-sm font-medium text-gb-gray mb-2">
                                    Nombre maximum de participants
                                </label>
                                <select
                                    name="max_participants"
                                    value={formData.max_participants}
                                    onChange={handleInputChange}
                                    className="w-full px-4 py-2 bg-gb-dark border border-gb-gray-dark rounded-md text-gb-white focus:outline-none focus:border-gb-purple"
                                >
                                    <option value={10}>10 participants</option>
                                    <option value={25}>25 participants</option>
                                    <option value={50}>50 participants</option>
                                    <option value={100}>100 participants</option>
                                    <option value={500}>500 participants</option>
                                </select>
                            </div>
                        </div>

                        {/* Informations WebRTC */}
                        {startImmediately && (
                            <div className="bg-gb-purple bg-opacity-10 border border-gb-purple border-opacity-30 rounded-lg p-4">
                                <h4 className="text-gb-purple font-medium mb-2">🎤 Test WebRTC</h4>
                                <p className="text-gb-light-gray text-sm">
                                    En démarrant maintenant, vous pourrez immédiatement tester :
                                </p>
                                <ul className="text-gb-light-gray text-sm mt-2 space-y-1">
                                    <li>• Connexion audio en temps réel</li>
                                    <li>• Chat en direct</li>
                                    <li>• Gestion des participants</li>
                                    <li>• Qualité audio et latence</li>
                                </ul>
                            </div>
                        )}

                        {/* Boutons */}
                        <div className="flex gap-3 pt-4">
                            <Button
                                type="button"
                                onClick={onClose}
                                variant="secondary"
                                className="flex-1"
                            >
                                Annuler
                            </Button>
                            <Button
                                type="submit"
                                disabled={isLoading || !formData.title.trim()}
                                variant="primary"
                                className="flex-1"
                            >
                                {isLoading ? 'Création...' : (startImmediately ? 'Créer et Démarrer' : 'Programmer')}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
};

export default CreateSpaceModal;