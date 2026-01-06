import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, usePage } from '@inertiajs/react';

export default function Edit({ auth, tenant }) {
    // Usamos useForm para manejar los datos y el envío
    const { data, setData, patch, processing, errors, recentlySuccessful } = useForm({
        name: tenant.name,
        primary_color: tenant.primary_color || '#d97706',
    });

    const submit = (e) => {
        e.preventDefault();
        patch(route('settings.update'));
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-200 leading-tight">Configuración del Negocio ⚙️</h2>}
        >
            <Head title="Configuración" />

            <div className="py-12 bg-zinc-900 min-h-screen">
                <div className="max-w-2xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-zinc-800 border border-zinc-700 shadow sm:rounded-lg p-6">
                        
                        <header>
                            <h2 className="text-lg font-medium text-white">Identidad de la Marca</h2>
                            <p className="mt-1 text-sm text-gray-400">
                                Personalizá cómo ven tus clientes la aplicación.
                            </p>
                        </header>

                        <form onSubmit={submit} className="mt-6 space-y-6">
                            
                            {/* NOMBRE DE LA BARBERÍA */}
                            <div>
                                <label className="block font-medium text-sm text-gray-300" htmlFor="name">
                                    Nombre del Local
                                </label>
                                <input
                                    id="name"
                                    type="text"
                                    className="mt-1 block w-full bg-zinc-900 border-zinc-600 text-white rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    required
                                />
                                {errors.name && <div className="text-red-500 text-sm mt-1">{errors.name}</div>}
                            </div>

                            {/* COLOR DE LA MARCA (COLOR PICKER) */}
                            <div>
                                <label className="block font-medium text-sm text-gray-300 mb-2" htmlFor="color">
                                    Color Principal
                                </label>
                                
                                <div className="flex items-center gap-4">
                                    {/* Input visual de color */}
                                    <input
                                        id="color"
                                        type="color"
                                        className="h-12 w-24 p-1 bg-zinc-900 border border-zinc-600 rounded cursor-pointer"
                                        value={data.primary_color}
                                        onChange={(e) => setData('primary_color', e.target.value)}
                                    />
                                    
                                    {/* Vista previa del botón */}
                                    <div className="text-sm text-gray-400">
                                        Así se verán tus botones:
                                        <button 
                                            type="button"
                                            className="ml-3 px-4 py-2 rounded text-white font-bold shadow transition-all hover:scale-105"
                                            style={{ backgroundColor: data.primary_color }}
                                        >
                                            Botón de Ejemplo
                                        </button>
                                    </div>
                                </div>
                                {errors.primary_color && <div className="text-red-500 text-sm mt-1">{errors.primary_color}</div>}
                            </div>

                            {/* BOTÓN GUARDAR */}
                            <div className="flex items-center gap-4">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="px-4 py-2 bg-white text-black font-bold rounded-md hover:bg-gray-200 transition"
                                >
                                    {processing ? 'Guardando...' : 'Guardar Cambios'}
                                </button>

                                {recentlySuccessful && (
                                    <p className="text-sm text-green-400 animate-pulse">¡Guardado!</p>
                                )}
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}