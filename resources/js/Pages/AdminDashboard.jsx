import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import axios from 'axios';

export default function AdminDashboard({ auth }) {
    const [appointments, setAppointments] = useState([]);

    useEffect(() => {
        fetchAppointments();
    }, []);

    const fetchAppointments = () => {
        axios.get('/api/admin/appointments')
            .then(res => setAppointments(res.data))
            .catch(err => console.error(err));
    };

    const handleMarkPaid = (id) => {
        axios.put(`/api/admin/appointments/${id}/pay`)
            .then(() => {
                setAppointments(appointments.map(app => 
                    app.id === id ? { ...app, payment_status: 'paid' } : app
                ));
            })
            .catch(error => alert('Error al cobrar: ' + error.message));
    };

    const handleCancel = (id) => {
        if (!confirm('¿Cancelar este turno manualmente?')) return;
        axios.put(`/api/appointments/${id}/cancel`)
            .then(() => {
                setAppointments(appointments.map(app => 
                    app.id === id ? { ...app, payment_status: 'cancelled' } : app
                ));
            })
            .catch(error => alert('Error al cancelar: ' + error.message));
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Panel de Control (Dueño) 💈</h2>}
        >
            <Head title="Admin" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div className="flex justify-between items-center mb-4">
                            <h3 className="text-lg font-bold">Agenda General</h3>
                            <button onClick={fetchAppointments} className="text-sm text-blue-600 hover:underline">🔄 Actualizar</button>
                        </div>
                        
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200 border">
                                <thead className="bg-gray-800 text-white">
                                    <tr>
                                        <th className="px-4 py-3 text-left">Inicio</th>
                                        <th className="px-4 py-3 text-left">Fin</th> {/* NUEVA COLUMNA */}
                                        <th className="px-4 py-3 text-left">Cliente</th>
                                        <th className="px-4 py-3 text-left">Servicio</th>
                                        <th className="px-4 py-3 text-left">Estado</th>
                                        <th className="px-4 py-3 text-left">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {appointments.map((app) => (
                                        <tr key={app.id} className="hover:bg-gray-50">
                                            {/* HORA INICIO */}
                                            <td className="px-4 py-3 font-bold text-gray-700">
                                                {new Date(app.scheduled_at).toLocaleString('es-AR', {
                                                    day: '2-digit', month: '2-digit', hour: '2-digit', minute:'2-digit'
                                                })}
                                            </td>

                                            {/* HORA FIN (Calculada en backend) */}
                                            <td className="px-4 py-3 font-bold text-red-600">
                                                {new Date(app.ends_at).toLocaleTimeString('es-AR', {
                                                    hour: '2-digit', minute:'2-digit'
                                                })}
                                            </td>

                                            <td className="px-4 py-3">
                                                <div className="font-semibold">{app.customer_name}</div>
                                                <div className="text-xs text-gray-500">{app.customer_phone}</div>
                                            </td>
                                            <td className="px-4 py-3 text-blue-600 font-bold">
                                                {app.service?.name}
                                            </td>
                                            <td className="px-4 py-3">
                                                 <span className={`px-2 py-1 text-xs rounded-full font-bold
                                                    ${app.payment_status === 'cancelled' ? 'bg-red-100 text-red-800' : 
                                                      app.payment_status === 'paid' ? 'bg-green-100 text-green-800' : 
                                                      'bg-yellow-100 text-yellow-800'}`}>
                                                    {app.payment_status === 'paid' ? 'PAGADO' : 
                                                     app.payment_status === 'cancelled' ? 'CANCELADO' : 'PENDIENTE'}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 flex gap-2">
                                                {app.payment_status !== 'cancelled' && (
                                                    <>
                                                        {app.payment_status === 'pending' && (
                                                            <button 
                                                                onClick={() => handleMarkPaid(app.id)}
                                                                className="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-xs"
                                                                title="Marcar como cobrado"
                                                            >
                                                                💰 Cobrar
                                                            </button>
                                                        )}
                                                        <button 
                                                            onClick={() => handleCancel(app.id)}
                                                            className="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs"
                                                            title="Cancelar turno"
                                                        >
                                                            ✖️
                                                        </button>
                                                    </>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                            {appointments.length === 0 && <p className="p-4 text-center text-gray-500">No hay turnos registrados.</p>}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}