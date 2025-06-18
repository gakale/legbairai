import React from 'react';
import CreateSpaceForm from '../components/spaces/CreateSpaceForm';
import Layout from '../components/Layout'; // Assuming a general Layout component

const CreateSpacePage = () => {
    return (
        <Layout>
            <div className="container mx-auto px-4 py-8 md:py-12">
                <CreateSpaceForm />
            </div>
        </Layout>
    );
};

export default CreateSpacePage;
