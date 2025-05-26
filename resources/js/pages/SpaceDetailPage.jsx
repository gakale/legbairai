import React from 'react';
import { useParams } from 'react-router-dom';
const SpaceDetailPage = () => {
    let { spaceId } = useParams();
    return <div className="container mx-auto py-10 px-4"><h2 className="text-3xl font-bold">Détail du Space: {spaceId}</h2></div>;
};
export default SpaceDetailPage;