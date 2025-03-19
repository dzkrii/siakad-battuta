import { IconBellRinging, IconChevronDown, IconChevronUp } from '@tabler/icons-react';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import { useState } from 'react';
import 'react-quill/dist/quill.snow.css'; // Import Quill styles for proper rendering

export default function AnnouncementCard({ announcements }) {
    const [expandedId, setExpandedId] = useState(null);

    const toggleExpand = (id) => {
        setExpandedId(expandedId === id ? null : id);
    };

    if (!announcements || announcements.length === 0) {
        return null;
    }

    return (
        <div className="mb-8 rounded-lg border bg-card p-6 shadow-sm">
            <div className="mb-4 flex items-center gap-2">
                <IconBellRinging className="h-5 w-5 text-primary" />
                <h3 className="text-lg font-semibold text-foreground">Pengumuman Terbaru</h3>
            </div>
            <div className="space-y-4">
                {announcements.map((announcement) => (
                    <div
                        key={announcement.id}
                        className="rounded-md border bg-card p-4 transition-all hover:bg-accent/10"
                    >
                        <div className="flex items-start justify-between">
                            <div className="flex-1">
                                <h4 className="font-medium text-foreground">{announcement.title}</h4>
                                <div className="mt-1 text-xs text-muted-foreground">
                                    {announcement.published_at ? (
                                        <span>
                                            Dipublikasikan:{' '}
                                            {format(new Date(announcement.published_at), 'dd MMMM yyyy', {
                                                locale: id,
                                            })}
                                        </span>
                                    ) : null}
                                </div>
                            </div>
                            <button
                                onClick={() => toggleExpand(announcement.id)}
                                className="flex h-8 w-8 items-center justify-center rounded-full hover:bg-accent/20"
                            >
                                {expandedId === announcement.id ? (
                                    <IconChevronUp className="h-4 w-4" />
                                ) : (
                                    <IconChevronDown className="h-4 w-4" />
                                )}
                            </button>
                        </div>
                        {expandedId === announcement.id && (
                            <div className="mt-4">
                                {/* Use a dedicated class to style the Quill content */}
                                <div
                                    className="ql-content prose-sm prose max-w-none"
                                    dangerouslySetInnerHTML={{ __html: announcement.content }}
                                />
                            </div>
                        )}
                    </div>
                ))}
            </div>
        </div>
    );
}
