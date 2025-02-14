import { Head, useForm, usePage } from '@inertiajs/react';

export default function SelectClassroom() {
    // Ambil data dari controller
    const { page_settings, classrooms } = usePage().props;

    // Inertia form handling
    const { data, setData, post, processing, errors } = useForm({
        classroom_id: '',
    });

    // Handle form submission
    const handleSubmit = (e) => {
        e.preventDefault();
        post(page_settings.action);
    };

    return (
        <div className="container mx-auto p-4">
            <Head>
                <title>{page_settings.title}</title>
            </Head>

            <h1 className="mb-4 text-2xl font-bold">{page_settings.title}</h1>
            <p className="mb-6">{page_settings.subtitle}</p>

            <h1>Keterangan Contoh Kelas :</h1>
            <h2>IF-1-PAGI = Informatika Semester 1 Kelas Pagi</h2>

            <form onSubmit={handleSubmit} className="mb-4 rounded bg-white px-8 pb-8 pt-6 shadow-md">
                {classrooms.map((classroom) => (
                    <div key={classroom.id} className="mb-4">
                        <label className="inline-flex items-center">
                            <input
                                type="radio"
                                name="classroom_id"
                                value={classroom.id}
                                checked={data.classroom_id === classroom.id}
                                onChange={(e) => setData('classroom_id', e.target.value)}
                                className="form-radio h-5 w-5 text-blue-600"
                            />
                            <span className="ml-2">{classroom.name}</span>
                        </label>
                    </div>
                ))}

                {errors.classroom_id && <div className="mb-4 text-sm text-red-500">{errors.classroom_id}</div>}

                <div className="flex items-center justify-between">
                    <button
                        type="submit"
                        disabled={processing}
                        className="focus:shadow-outline rounded bg-blue-500 px-4 py-2 font-bold text-white hover:bg-blue-700 focus:outline-none"
                    >
                        {processing ? 'Memproses...' : 'Pilih Kelas'}
                    </button>
                </div>
            </form>
        </div>
    );
}
