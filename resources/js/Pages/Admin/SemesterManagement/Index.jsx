import HeaderTitle from '@/Components/HeaderTitle';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader } from '@/Components/ui/card';
import AppLayout from '@/Layouts/AppLayout';
import { Link } from '@inertiajs/react';
import { IconCalendarUp, IconChevronRight, IconSchool, IconUsers } from '@tabler/icons-react';

export default function Index(props) {
    const { students_by_semester, semesters } = props;

    return (
        <div className="flex w-full flex-col pb-32">
            <div className="mb-8 flex flex-col items-start justify-between gap-y-4 lg:flex-row lg:items-center">
                <HeaderTitle
                    title={props.page_settings.title}
                    subtitle={props.page_settings.subtitle}
                    icon={IconCalendarUp}
                />
            </div>

            <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                {semesters.map((semester) => (
                    <Card key={semester} className="transition-shadow hover:shadow-lg">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <div className="flex items-center space-x-2">
                                <div className="rounded-full bg-primary/10 p-2">
                                    <IconSchool className="size-5 text-primary" />
                                </div>
                                <h3 className="text-lg font-semibold">Semester {semester}</h3>
                            </div>
                            <span className="rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold">
                                {students_by_semester[semester] ? students_by_semester[semester].length : 0} Mahasiswa
                            </span>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center justify-between">
                                <div className="text-sm text-muted-foreground">
                                    Mahasiswa dengan status KRS approved
                                </div>
                                <Button variant="primary" size="sm" asChild>
                                    <Link href={route('admin.semester-management.show', semester)}>
                                        Detail <IconChevronRight className="ml-2 size-4" />
                                    </Link>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                ))}
            </div>

            {semesters.length === 0 && (
                <div className="mt-10 text-center">
                    <IconUsers className="mx-auto size-12 text-muted-foreground" />
                    <h3 className="mt-4 text-lg font-medium">Tidak ada data mahasiswa</h3>
                    <p className="mt-2 text-sm text-muted-foreground">Belum ada mahasiswa yang terdaftar di sistem.</p>
                </div>
            )}
        </div>
    );
}

Index.layout = (page) => <AppLayout title={page.props.page_settings.title} children={page} />;
