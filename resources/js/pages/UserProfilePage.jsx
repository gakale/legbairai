import React from 'react';
import { useParams } from 'react-router-dom';
const UserProfilePage = () => {
    let { userId } = useParams();
    return <div className="container mx-auto py-10 px-4"><h2 className="text-3xl font-bold">Profil Utilisateur: {userId}</h2></div>;
};
export default UserProfilePage;