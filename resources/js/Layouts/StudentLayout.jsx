import Banner from '@/Components/Banner';
import { Card, CardContent } from '@/Components/ui/card';
import { Toaster } from '@/Components/ui/sonner';
import { flashMessage } from '@/lib/utils';
import { Head, usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import HeaderStudentLayout from './Partials/HeaderStudentLayout';

export default function StudentLayout({ children, title }) {
    const checkFee = usePage().props.checkFee;
    const { url } = usePage();
    const auth = usePage().props.auth.user;

    const flash = flashMessage(usePage());

    useEffect(() => {
        if (flash && flash.message && flash.type === 'warning') toast[flash.type](flash.message);
    }, [flash]);

    return (
        <>
            <Head title={title} />
            <Toaster position="top-center" richColors />
            <div className="min-h-full">
                <div className="bg-gradient-to-r from-green-500 via-green-600 to-green-700 pb-32">
                    {/* Header Layout */}
                    <HeaderStudentLayout auth={auth} url={url} />
                </div>

                <main className="-mt-32 px-6 pb-12 lg:px-28">
                    <Card>
                        <CardContent className="p-6">{children}</CardContent>
                    </Card>
                    {/* ccheckFee */}
                    {checkFee === false && (
                        <Banner message=" Harap melakukan pembayaran uang kuliah tunggal terlebih dahulu" />
                    )}
                </main>
            </div>
        </>
    );
}
