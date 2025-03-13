import EmptyState from '@/Components/EmptyState';
import Grades from '@/Components/Grades';
import HeaderTitle from '@/Components/HeaderTitle';
import PaginationTable from '@/Components/PaginationTable';
import ShowFilter from '@/Components/ShowFilter';
import { Button } from '@/Components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import StudentLayout from '@/Layouts/StudentLayout';
import { formatDateIndo } from '@/lib/utils';
import { IconDownload, IconSchool } from '@tabler/icons-react';
import { useState } from 'react';

export default function Index(props) {
    const { data: studyResults, meta, links } = props.studyResults;
    const [params, setParams] = useState(props.state);

    return (
        <div className="flex w-full flex-col pb-32">
            <div className="mb-8 flex flex-col items-start justify-between gap-y-4 lg:flex-row lg:items-center">
                <HeaderTitle
                    title={props.page_settings.title}
                    subtitle={props.page_settings.subtitle}
                    icon={IconSchool}
                />
            </div>
            <div className="flex flex-col gap-y-8">
                {/* Show Filter */}
                <ShowFilter params={params} />
                {studyResults.length === 0 ? (
                    <EmptyState
                        icon={IconSchool}
                        title="Tidak ada kartu hasil studi"
                        subtitle="Mulailah dengan membuat kartu hasil studi baru."
                    />
                ) : (
                    <Table className="w-full">
                        <TableHeader>
                            <TableRow>
                                <TableHead>#</TableHead>
                                <TableHead>Tahun Ajaran</TableHead>
                                <TableHead>Semester</TableHead>
                                <TableHead>IPS</TableHead>
                                <TableHead>Dibuat Pada</TableHead>
                                <TableHead>Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {studyResults.map((studyResult, index) => (
                                <TableRow key={index}>
                                    <TableCell>{index + 1 + (meta.current_page - 1) * meta.per_page}</TableCell>
                                    <TableCell>{studyResult.academicYear.name}</TableCell>
                                    <TableCell>{studyResult.semester}</TableCell>
                                    <TableCell>{studyResult.gpa}</TableCell>
                                    <TableCell>{formatDateIndo(studyResult.created_at)}</TableCell>
                                    <TableCell>
                                        <div className="flex items-center gap-x-1">
                                            <Grades studyResult={studyResult} grades={studyResult.grades} />
                                            <Button variant="green" size="sm" asChild>
                                                <a
                                                    href={route('students.study-results.download', studyResult.id)}
                                                    target="_blank"
                                                >
                                                    <IconDownload className="size-4" />
                                                </a>
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                )}
                <div className="flex w-full flex-col items-center justify-between gap-y-2 lg:flex-row">
                    <p className="text-sm text-muted-foreground">
                        Menampilkan <span className="font-medium">{meta.from ?? 0}</span> -{' '}
                        <span className="font-medium">{meta.to ?? 0}</span> dari {meta.total} kartu hasil studi
                    </p>
                    <div className="overflow-x-auto">
                        {meta.has_pages && <PaginationTable meta={meta} links={links} />}
                    </div>
                </div>
            </div>
        </div>
    );
}

Index.layout = (page) => <StudentLayout title={page.props.page_settings.title} children={page} />;
