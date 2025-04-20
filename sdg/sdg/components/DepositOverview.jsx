import React from 'react';

const DepositOverview = () => {
    return (
        <div className="bg-gray-50 min-h-screen">
            <div className="container mx-auto px-4 py-8">
                {/* Main Grid Container */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {/* Cubic Cluster 1 */}
                    <div className="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-shadow duration-300">
                        <div className="h-48 bg-green-50 rounded-xl mb-4"></div>
                        <div className="h-4 bg-gray-100 rounded w-2/3 mb-2"></div>
                        <div className="h-3 bg-gray-50 rounded w-1/2"></div>
                    </div>

                    {/* Cubic Cluster 2 */}
                    <div className="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-shadow duration-300">
                        <div className="h-48 bg-green-50 rounded-xl mb-4"></div>
                        <div className="h-4 bg-gray-100 rounded w-2/3 mb-2"></div>
                        <div className="h-3 bg-gray-50 rounded w-1/2"></div>
                    </div>

                    {/* Cubic Cluster 3 */}
                    <div className="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-shadow duration-300">
                        <div className="h-48 bg-green-50 rounded-xl mb-4"></div>
                        <div className="h-4 bg-gray-100 rounded w-2/3 mb-2"></div>
                        <div className="h-3 bg-gray-50 rounded w-1/2"></div>
                    </div>

                    {/* Cubic Cluster 4 */}
                    <div className="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-shadow duration-300">
                        <div className="h-48 bg-green-50 rounded-xl mb-4"></div>
                        <div className="h-4 bg-gray-100 rounded w-2/3 mb-2"></div>
                        <div className="h-3 bg-gray-50 rounded w-1/2"></div>
                    </div>

                    {/* Cubic Cluster 5 */}
                    <div className="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-shadow duration-300">
                        <div className="h-48 bg-green-50 rounded-xl mb-4"></div>
                        <div className="h-4 bg-gray-100 rounded w-2/3 mb-2"></div>
                        <div className="h-3 bg-gray-50 rounded w-1/2"></div>
                    </div>

                    {/* Cubic Cluster 6 */}
                    <div className="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-shadow duration-300">
                        <div className="h-48 bg-green-50 rounded-xl mb-4"></div>
                        <div className="h-4 bg-gray-100 rounded w-2/3 mb-2"></div>
                        <div className="h-3 bg-gray-50 rounded w-1/2"></div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default DepositOverview; 