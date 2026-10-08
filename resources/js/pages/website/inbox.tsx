import { Head, router, usePage } from '@inertiajs/react';
import { Check, Inbox as InboxIcon } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { inbox, show } from '@/routes/website';
import { read } from '@/routes/website/inbox';
import type { WebsiteSubmission } from '@/types';

type Props = {
    submissions: WebsiteSubmission[];
};

export default function WebsiteInbox({ submissions }: Props) {
    const { currentTeam } = usePage().props;

    const markAsRead = (submission: WebsiteSubmission) => {
        router.visit(
            read({
                current_team: currentTeam?.slug ?? '',
                submission: submission.id,
            }),
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Inbox" />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-6">
                <Heading
                    title="Inbox"
                    description="Messages and sign-ups sent from your website's contact and newsletter forms."
                />

                {submissions.length === 0 ? (
                    <div className="flex flex-col items-center gap-2 rounded-xl border border-dashed p-10 text-center">
                        <InboxIcon className="size-6 text-muted-foreground" />
                        <p className="font-medium">Nothing here yet</p>
                        <p className="max-w-sm text-sm text-muted-foreground">
                            Add a Contact or Newsletter section in the editor
                            and new messages will show up here.
                        </p>
                    </div>
                ) : (
                    <ul className="divide-y rounded-xl border">
                        {submissions.map((submission) => (
                            <li
                                key={submission.id}
                                className={cn(
                                    'flex flex-col gap-3 p-4',
                                    !submission.isRead && 'bg-accent/30',
                                )}
                            >
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <div className="flex items-center gap-2">
                                        <span className="font-medium">
                                            {submission.sender ||
                                                'Anonymous visitor'}
                                        </span>
                                        <Badge variant="secondary">
                                            {submission.typeLabel}
                                        </Badge>
                                        {submission.isRead ? null : (
                                            <Badge>New</Badge>
                                        )}
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <span className="text-sm text-muted-foreground">
                                            {submission.receivedAt}
                                        </span>
                                        {submission.isRead ? null : (
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() =>
                                                    markAsRead(submission)
                                                }
                                            >
                                                <Check />
                                                Mark as read
                                            </Button>
                                        )}
                                    </div>
                                </div>

                                <dl className="grid gap-x-6 gap-y-1 text-sm sm:grid-cols-[max-content_1fr]">
                                    {Object.entries(submission.fields).map(
                                        ([field, value]) => (
                                            <div
                                                key={field}
                                                className="contents"
                                            >
                                                <dt className="text-muted-foreground capitalize">
                                                    {field.replaceAll('_', ' ')}
                                                </dt>
                                                <dd className="break-words whitespace-pre-line">
                                                    {value}
                                                </dd>
                                            </div>
                                        ),
                                    )}
                                </dl>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}

WebsiteInbox.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Website',
            href: props.currentTeam ? show(props.currentTeam.slug) : '/',
        },
        {
            title: 'Inbox',
            href: props.currentTeam ? inbox(props.currentTeam.slug) : '/',
        },
    ],
});
