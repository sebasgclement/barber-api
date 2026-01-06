import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import axios from 'axios';

export default function MyAppointments({ auth }) {
    const [appointments, setAppointments] = useState([]);
    const [loading, setLoading] = useState(true);

    // 1. Cargar los turnos al entrar
    useEffect(() => {
        axios.get('/api/my-appointments')
            .then(response => {
                setAppointments(response.data);
                setLoading(false);
            })
            .catch(error => {
                console.error("Error cargando turnos:", error);
                setLoading(false);
            });
    }, []);

    // 2. Función para cancelar
    const handleCancel = (id) => {
        if (!confirm('¿Estás seguro de que quieres cancelar este turno?')) return;

        axios.put(`/api/appointments/${id}/cancel`)
            .then(() => {
                alert('Turno cancelado.');
                // Recargamos la lista localmente para ver el cambio
                setAppointments(appointments.map(app => 
                    app.id === id ? { ...app, payment_status: 'cancelled' } : app
                ));
            })
            .catch(error => {
                console.error("Error al cancelar:", error);
                alert('No se pudo cancelar el turno.');
            });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Mis Turnos</h2>}
        >
            <Head title="Mis Turnos" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        
                        {loading ? (
                            <p>Cargando turnos...</p>
                        ) : appointments.length === 0 ? (
                            <p>No tienes turnos registrados.</p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha y Hora</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Servicio</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {appointments.map((app) => (
                                            <tr key={app.id}>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    {new Date(app.scheduled_at).toLocaleString()}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    {app.service ? app.service.name : 'Servicio eliminado'}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                        ${app.payment_status === 'cancelled' ? 'bg-red-100 text-red-800' : 
                                                          app.payment_status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                                          'bg-green-100 text-green-800'}`}>
                                                        {app.payment_status === 'cancelled' ? 'Cancelado' : 
                                                         app.payment_status === 'pending' ? 'Pendiente' : 'Pagado'}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    {app.payment_status !== 'cancelled' && (
                                                        <button 
                                                            onClick={() => handleCancel(app.id)}
                                                            className="text-red-600 hover:text-red-900 font-bold"
                                                        >
                                                            Cancelar
                                                        </button>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}

                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}