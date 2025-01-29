import CalendarSchedule from '@/Components/CalendarSchedule';
import HeaderTitle from '@/Components/HeaderTitle';
import { Alert, AlertDescription } from '@/Components/ui/alert';
import StudentLayout from '@/Layouts/StudentLayout';
import { usePage } from '@inertiajs/react';
import { IconCalendar } from '@tabler/icons-react';

export default function Index(props) {
    const schedules = props.scheduleTable;
    const days = props.days;
    const auth = usePage().props.auth.user;

    return (
        <div className="flex w-full flex-col pb-32">
            <div className="mb-8 flex flex-col items-start justify-between gap-y-4 lg:flex-row lg:items-center">
                <HeaderTitle
                    title={props.page_settings.title}
                    subtitle={props.page_settings.subtitle}
                    icon={IconCalendar}
                />
            </div>
            <div className="flex flex-col gap-y-8">
                <CalendarSchedule days={days} schedules={schedules} student={auth.student} />
                <div className="flex sm:hidden">
                    <Alert variant="destructive">
                        <AlertDescription>Jadwal hanya bisa dilihat dalam mode desktop</AlertDescription>
                    </Alert>
                </div>
            </div>
        </div>
    );
}

Index.layout = (page) => <StudentLayout title={page.props.page_settings.title} children={page} />;
