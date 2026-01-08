import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import { useEffect, useState } from 'react';

export default function AdminDashboard({ auth }) {
    const [data, setData] = useState({ today: [], future: [] });

    useEffect(() => {
        fetchAppointments();
    }, []);

    const fetchAppointments = () => {
        axios.get('/api/admin/appointments')
            .then(res => setData(res.data))
            .catch(err => console.error(err));
    };

    const updateLocalState = (id, newStatus) => {
        setData(prev => ({
            today: prev.today.map(app => app.id === id ? { ...app, payment_status: newStatus } : app),
            future: prev.future.map(app => app.id === id ? { ...app, payment_status: newStatus } : app)
        }));
    };

    const handleMarkPaid = (id) => {
        axios.put(`/api/admin/appointments/${id}/pay`)
            .then(() => updateLocalState(id, 'paid'))
            .catch(error => alert('Error al cobrar: ' + error.message));
    };

    const handleCancel = (id) => {
        if (!confirm('¿Cancelar este turno manualmente?')) return;
        axios.put(`/api/appointments/${id}/cancel`)
            .then(() => updateLocalState(id, 'cancelled'))
            .catch(error => alert('Error al cancelar: ' + error.message));
    };

    // --- COMPONENTE INTERNO CON ACORDEÓN ---
    const AppointmentTable = ({ appointments, title, emptyMessage, defaultOpen = true }) => {
        const [isOpen, setIsOpen] = useState(defaultOpen); // Estado para abrir/cerrar

        return (
            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-8 transition-all">
                {/* CABECERA CLICKEABLE */}
                <div 
                    onClick={() => setIsOpen(!isOpen)} 
                    className="flex justify-between items-center cursor-pointer border-b pb-2 mb-4 hover:bg-gray-50 p-2 rounded"
                >
                    <h3 className="text-lg font-bold text-gray-800 flex items-center gap-2">
                        {title} 
                        <span className="text-sm font-normal text-gray-500">({appointments.length})</span>
                    </h3>
                    <span className="text-gray-500 text-xl font-bold">
                        {isOpen ? '−' : '+'}
                    </span>
                </div>

                {/* CONTENIDO OCULTO/VISIBLE */}
                {isOpen && (
                    <div className="overflow-x-auto animate-fadeIn">
                        <table className="min-w-full divide-y divide-gray-200 border">
                            <thead className="bg-gray-800 text-white">
                                <tr>
                                    <th className="px-4 py-3 text-left">Horario</th>
                                    <th className="px-4 py-3 text-left">Cliente</th>
                                    <th className="px-4 py-3 text-left">Servicio</th>
                                    <th className="px-4 py-3 text-left">Estado</th>
                                    <th className="px-4 py-3 text-left">Acciones</th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {appointments.map((app) => (
                                    <tr key={app.id} className="hover:bg-gray-50">
                                        <td className="px-4 py-3">
                                            <div className="font-bold text-gray-800 text-lg">
                                                {new Date(app.scheduled_at).toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' })}
                                            </div>
                                            <div className="text-xs text-gray-500">
                                                {new Date(app.scheduled_at).toLocaleDateString('es-AR', { day: 'numeric', month: 'short' })}
                                            </div>
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
                                                            onClick={(e) => { e.stopPropagation(); handleMarkPaid(app.id); }}
                                                            className="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-xs"
                                                            title="Marcar como cobrado"
                                                        >
                                                            💰
                                                        </button>
                                                    )}
                                                    <button 
                                                        onClick={(e) => { e.stopPropagation(); handleCancel(app.id); }}
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
                        {appointments.length === 0 && (
                            <p className="p-4 text-center text-gray-500 italic">{emptyMessage}</p>
                        )}
                    </div>
                )}
            </div>
        );
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">Panel de Control (Dueño) 💈</h2>
                    <button onClick={fetchAppointments} className="text-sm bg-blue-100 text-blue-700 px-3 py-1 rounded hover:bg-blue-200">
                        🔄 Actualizar
                    </button>
                </div>
            }
        >
            <Head title="Admin Dashboard" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    
                    {/* Tabla HOY (Abierta por defecto) */}
                    <AppointmentTable 
                        appointments={data.today} 
                        title="📅 Turnos de HOY" 
                        emptyMessage="No hay más turnos por hoy."
                        defaultOpen={true}
                    />

                    {/* Tabla FUTUROS (Cerrada por defecto para que no moleste) */}
                    <AppointmentTable 
                        appointments={data.future} 
                        title="🚀 Próximos Turnos" 
                        emptyMessage="No hay reservas futuras." 
                        defaultOpen={false}
                    />

                </div>
            </div>
        </AuthenticatedLayout>
    );
}