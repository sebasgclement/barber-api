import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import axios from 'axios';

export default function Dashboard({ auth }) {
    const [availableSlots, setAvailableSlots] = useState([]);
    const [loadingSlots, setLoadingSlots] = useState(false);
    
    // NUEVO: Estado para cargar los servicios reales de la DB
    const [services, setServices] = useState([]);

    const { data, setData, post, processing, errors } = useForm({
        service_id: '',      // Lo dejamos vacío al inicio
        date: '',            
        time: '',            
        scheduled_at: '',    
        customer_name: auth.user.name,
        customer_phone: '',
        customer_email: auth.user.email,
    });

    // 1. Cargar servicios al iniciar
    useEffect(() => {
        // Podés crear esta ruta rápida en routes/api.php o pasarla como prop desde Inertia
        // Por ahora asumimos que existe una ruta pública o protegida
        axios.get('/api/services') 
            .then(res => {
                setServices(res.data);
                if(res.data.length > 0) {
                    setData('service_id', res.data[0].id); // Seleccionar el primero por defecto
                }
            })
            .catch(err => console.error("Error cargando servicios", err));
    }, []);

    // 2. Buscar huecos cuando cambia Fecha o Servicio
    useEffect(() => {
        if (data.date && data.service_id) {
            setAvailableSlots([]); // Limpiar anteriores
            setLoadingSlots(true);
            
            axios.get(`/api/slots?date=${data.date}&service_id=${data.service_id}`)
                .then(response => {
                    setAvailableSlots(response.data);
                    setLoadingSlots(false);
                })
                .catch(error => {
                    console.error("Error cargando horarios", error);
                    setLoadingSlots(false);
                });
        }
    }, [data.date, data.service_id]);

    // 3. Enviar Formulario
    const submit = (e) => {
        e.preventDefault();
        const finalDateTime = `${data.date} ${data.time}:00`;

        const payload = {
            ...data,
            scheduled_at: finalDateTime
        };

        axios.post('/api/appointments', payload)
            .then(res => {
                alert('¡Turno reservado con éxito! 💈');
                window.location.href = '/mis-turnos'; 
            })
            .catch(err => {
                if(err.response && err.response.data.message) {
                    alert(err.response.data.message);
                } else {
                    alert('Ocurrió un error al reservar.');
                }
            });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Reservar Turno</h2>}
        >
            <Head title="Reservar" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        
                        <form onSubmit={submit} className="space-y-6 max-w-lg mx-auto">
                            
                            {/* SERVICIO DINÁMICO */}
                            <div>
                                <label className="block font-medium text-gray-700 mb-2">1. Elegí el servicio</label>
                                <select 
                                    value={data.service_id}
                                    onChange={e => setData('service_id', e.target.value)}
                                    className="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                >
                                    {services.length === 0 && <option>Cargando servicios...</option>}
                                    
                                    {services.map(service => (
                                        <option key={service.id} value={service.id}>
                                            {/* AQUÍ SE VE LA MAGIA: Nombre (Minutos) - Precio */}
                                            {service.name} ({service.duration_minutes} min) - ${service.price}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {/* FECHA */}
                            <div>
                                <label className="block font-medium text-gray-700 mb-2">2. ¿Qué día venís?</label>
                                <input 
                                    type="date" 
                                    min={new Date().toISOString().split('T')[0]} 
                                    value={data.date}
                                    onChange={e => {
                                        setData('date', e.target.value);
                                        setData('time', '');
                                    }}
                                    className="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    required 
                                />
                            </div>

                            {/* GRILLA DE HORARIOS */}
                            {data.date && (
                                <div>
                                    <label className="block font-medium text-gray-700 mb-2">3. Seleccioná un horario disponible</label>
                                    
                                    {loadingSlots ? (
                                        <p className="text-gray-500 text-sm animate-pulse">Buscando huecos libres...</p>
                                    ) : (
                                        <div className="grid grid-cols-4 gap-2">
                                            {availableSlots.length > 0 ? (
                                                availableSlots.map((slot) => (
                                                    <button
                                                        key={slot}
                                                        type="button"
                                                        onClick={() => setData('time', slot)}
                                                        className={`py-2 px-3 rounded text-sm font-bold border transition
                                                            ${data.time === slot 
                                                                ? 'bg-blue-600 text-white border-blue-600 ring-2 ring-blue-300' 
                                                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                                                            }`}
                                                    >
                                                        {slot}
                                                    </button>
                                                ))
                                            ) : (
                                                <p className="col-span-4 text-red-500 text-sm text-center bg-red-50 p-2 rounded">
                                                    No quedan turnos para este servicio hoy. 😔
                                                </p>
                                            )}
                                        </div>
                                    )}
                                    {errors.scheduled_at && <div className="text-red-500 text-sm mt-1">{errors.scheduled_at}</div>}
                                </div>
                            )}

                            {/* TELÉFONO */}
                            <div className="pt-4 border-t">
                                <label className="block font-medium text-gray-700 mb-2">Tu Teléfono</label>
                                <input 
                                    type="text" 
                                    value={data.customer_phone}
                                    onChange={e => setData('customer_phone', e.target.value)}
                                    className="w-full border-gray-300 rounded-md shadow-sm"
                                    placeholder="Ej: 3492 123456"
                                    required 
                                />
                            </div>

                            {/* BOTÓN */}
                            <button 
                                type="submit" 
                                disabled={processing || !data.time}
                                className={`w-full py-3 rounded-lg text-white font-bold text-lg shadow-md transition
                                    ${(processing || !data.time) 
                                        ? 'bg-gray-400 cursor-not-allowed' 
                                        : 'bg-gray-900 hover:bg-black'}`}
                            >
                                {processing ? 'Reservando...' : 'Confirmar Reserva ✅'}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}