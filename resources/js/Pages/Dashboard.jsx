import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage } from '@inertiajs/react'; // Importamos usePage para sacar datos del usuario
import { useState, useEffect } from 'react';
import axios from 'axios'; // Usamos Axios para las peticiones (es más fácil que fetch)

export default function Dashboard() {
    const user = usePage().props.auth.user; // <-- ACÁ SACAMOS LOS DATOS DE QUIEN ESTÁ LOGUEADO

    const [services, setServices] = useState([]);
    const [selectedService, setSelectedService] = useState(null);
    const [dateTime, setDateTime] = useState('');
    const [phone, setPhone] = useState('');
    
    // Para mostrar mensajes de éxito o error
    const [message, setMessage] = useState(null); 

    // 1. Cargar servicios al iniciar
    useEffect(() => {
        axios.get('/api/services')
            .then(res => setServices(res.data))
            .catch(err => console.error(err));
    }, []);

    // 2. Función para reservar
    const handleReserve = () => {
        if (!selectedService || !dateTime || !phone) {
            alert("Por favor completá todos los datos");
            return;
        }

        // Formateamos la fecha para que le guste a MySQL (reemplazamos la T por espacio)
        // El input viene como "2026-01-07T10:00", lo pasamos a "2026-01-07 10:00:00"
        const formattedDate = dateTime.replace('T', ' ') + ':00';

        const payload = {
            service_id: selectedService.id,
            scheduled_at: formattedDate,
            customer_name: user.name,   // Usamos el nombre de tu usuario logueado
            customer_email: user.email, // Usamos el email de tu usuario logueado
            customer_phone: phone
        };

        axios.post('/api/appointments', payload)
            .then(response => {
                setMessage({ type: 'success', text: '¡Turno reservado con éxito! ID: ' + response.data.data.id });
                setSelectedService(null); // Reseteamos la selección
                setDateTime('');
            })
            .catch(error => {
                // Si el backend devuelve error (ej: horario ocupado o cerrado)
                const errorMsg = error.response?.data?.message || 'Ocurrió un error inesperado';
                setMessage({ type: 'error', text: errorMsg });
            });
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Reserva tu Turno ✂️</h2>}
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    
                    {/* MENSAJES DE ALERTA */}
                    {message && (
                        <div className={`mb-4 p-4 rounded text-white ${message.type === 'success' ? 'bg-green-500' : 'bg-red-500'}`}>
                            {message.text}
                        </div>
                    )}

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                        
                        {/* COLUMNA IZQUIERDA: SERVICIOS */}
                        <div>
                            <h3 className="mb-4 text-xl font-bold text-gray-800 dark:text-white px-2">1. Elegí un servicio:</h3>
                            <div className="space-y-4">
                                {services.map((service) => (
                                    <div 
                                        key={service.id} 
                                        onClick={() => setSelectedService(service)}
                                        className={`p-4 cursor-pointer border-2 rounded-lg transition-all ${
                                            selectedService?.id === service.id 
                                            ? 'border-red-500 bg-red-50 dark:bg-red-900/20' 
                                            : 'border-transparent bg-white dark:bg-gray-800 hover:border-gray-300'
                                        }`}
                                    >
                                        <div className="flex justify-between items-center">
                                            <span className="font-bold text-gray-900 dark:text-white">{service.name}</span>
                                            <span className="text-red-500 font-bold">${service.price}</span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* COLUMNA DERECHA: FORMULARIO (Solo aparece si elegiste servicio) */}
                        <div>
                            {selectedService ? (
                                <div className="p-6 bg-white dark:bg-gray-800 rounded-lg shadow">
                                    <h3 className="text-xl font-bold mb-4 text-gray-800 dark:text-white">
                                        Reservando: <span className="text-red-500">{selectedService.name}</span>
                                    </h3>

                                    <div className="mb-4">
                                        <label className="block text-gray-700 dark:text-gray-300 mb-2">¿Cuándo venís?</label>
                                        <input 
                                            type="datetime-local" 
                                            className="w-full p-2 border rounded dark:bg-gray-700 dark:text-white"
                                            value={dateTime}
                                            onChange={(e) => setDateTime(e.target.value)}
                                        />
                                        <p className="text-xs text-gray-500 mt-1">Horario: 09:00 a 20:00</p>
                                    </div>

                                    <div className="mb-4">
                                        <label className="block text-gray-700 dark:text-gray-300 mb-2">Tu Teléfono</label>
                                        <input 
                                            type="text" 
                                            placeholder="Ej: 3492 123456"
                                            className="w-full p-2 border rounded dark:bg-gray-700 dark:text-white"
                                            value={phone}
                                            onChange={(e) => setPhone(e.target.value)}
                                        />
                                    </div>

                                    <button 
                                        onClick={handleReserve}
                                        className="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded transition-colors"
                                    >
                                        CONFIRMAR RESERVA ($ {selectedService.price})
                                    </button>
                                </div>
                            ) : (
                                <div className="h-full flex items-center justify-center p-10 border-2 border-dashed border-gray-300 rounded-lg">
                                    <p className="text-gray-500">👈 Seleccioná un servicio para continuar</p>
                                </div>
                            )}
                        </div>

                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}