import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import {
    ExternalLink,
    Globe,
    House,
    Inbox,
    PanelsTopLeft,
    PencilRuler,
} from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { inbox, show, status, store } from '@/routes/website';
import type { Website, WebsiteFamily } from '@/types';

type Props = {
    website: Website | null;
    families: WebsiteFamily[];
};

export default function WebsiteShow({ website, families }: Props) {
    return (
        <>
            <Head title="Website" />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-8 p-4 md:p-6">
                {website ? (
                    <WebsiteOverview website={website} />
                ) : (
                    <CreateWebsite families={families} />
                )}
            </div>
        </>
    );
}

function CreateWebsite({ families }: { families: WebsiteFamily[] }) {
    const { currentTeam } = usePage().props;
    const [family, setFamily] = useState(families[0]?.value ?? '');

    return (
        <section className="flex flex-col gap-6">
            <Heading
                title="Create your website"
                description={`Pick a starting style for ${currentTeam?.name ?? 'your team'}. Every page, color and font stays editable afterwards.`}
            />

            <Form
                {...store.form(currentTeam?.slug ?? '')}
                className="flex flex-col gap-6"
            >
                {({ errors, processing }) => (
                    <>
                        <input type="hidden" name="family" value={family} />

                        <div
                            role="radiogroup"
                            aria-label="Website style"
                            className="grid gap-3 sm:grid-cols-2"
                        >
                            {families.map((option) => (
                                <button
                                    key={option.value}
                                    type="button"
                                    role="radio"
                                    aria-checked={family === option.value}
                                    onClick={() => setFamily(option.value)}
                                    className={cn(
                                        'flex flex-col gap-1 rounded-xl border p-4 text-left transition-colors',
                                        'hover:bg-accent/50 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                                        family === option.value &&
                                            'border-primary bg-accent/40 ring-1 ring-primary',
                                    )}
                                >
                                    <span className="font-medium">
                                        {option.label}
                                    </span>
                                    <span className="text-sm text-muted-foreground">
                                        {option.description}
                                    </span>
                                </button>
                            ))}
                        </div>

                        <InputError message={errors.family} />

                        <div>
                            <Button disabled={processing || family === ''}>
                                {processing ? <Spinner /> : <Globe />}
                                Create website
                            </Button>
                        </div>
                    </>
                )}
            </Form>
        </section>
    );
}

function WebsiteOverview({ website }: { website: Website }) {
    const { currentTeam } = usePage().props;
    const [updating, setUpdating] = useState(false);
    const homepage = website.pages.find((page) => page.isHomepage);

    const setLive = (live: boolean) => {
        router.visit(status(currentTeam?.slug ?? ''), {
            data: { live },
            preserveScroll: true,
            onStart: () => setUpdating(true),
            onFinish: () => setUpdating(false),
        });
    };

    return (
        <>
            <header className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div className="flex flex-col gap-2">
                    <div className="flex items-center gap-2">
                        <h1 className="text-xl font-semibold tracking-tight">
                            {website.name}
                        </h1>
                        <Badge
                            variant={website.isLive ? 'default' : 'secondary'}
                        >
                            {website.statusLabel}
                        </Badge>
                    </div>
                    {website.isLive ? (
                        <a
                            href={website.url}
                            target="_blank"
                            rel="noreferrer"
                            className="inline-flex items-center gap-1 text-sm text-muted-foreground underline-offset-4 hover:underline"
                        >
                            {website.url}
                            <ExternalLink className="size-3.5" />
                        </a>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            Visitors can't see this website until you put it
                            online.
                        </p>
                    )}
                </div>

                <div className="flex flex-wrap gap-2">
                    <Button
                        variant="outline"
                        disabled={updating}
                        onClick={() => setLive(!website.isLive)}
                    >
                        {updating ? <Spinner /> : null}
                        {website.isLive ? 'Take offline' : 'Put online'}
                    </Button>
                    {homepage ? (
                        <Button asChild>
                            <a href={homepage.editUrl}>
                                <PencilRuler />
                                Open editor
                            </a>
                        </Button>
                    ) : null}
                </div>
            </header>

            <section className="flex flex-col gap-3">
                <Heading
                    variant="small"
                    title="Pages"
                    description="Add, rename or delete pages from the editor's page menu."
                />

                <ul className="divide-y rounded-xl border">
                    {website.pages.map((page) => (
                        <li
                            key={page.id}
                            className="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div className="flex min-w-0 flex-col gap-1">
                                <div className="flex items-center gap-2">
                                    {page.isHomepage ? (
                                        <House className="size-4 text-muted-foreground" />
                                    ) : null}
                                    <span className="truncate font-medium">
                                        {page.name}
                                    </span>
                                    {page.isPublished ? null : (
                                        <Badge variant="outline">Draft</Badge>
                                    )}
                                </div>
                                <span className="text-sm text-muted-foreground">
                                    {page.path}
                                    {page.updatedAt
                                        ? ` · edited ${page.updatedAt}`
                                        : ''}
                                </span>
                            </div>

                            <div className="flex shrink-0 gap-2">
                                {page.url ? (
                                    <Button variant="ghost" size="sm" asChild>
                                        <a
                                            href={page.url}
                                            target="_blank"
                                            rel="noreferrer"
                                        >
                                            <ExternalLink />
                                            View
                                        </a>
                                    </Button>
                                ) : null}
                                <Button variant="outline" size="sm" asChild>
                                    <a href={page.editUrl}>
                                        <PencilRuler />
                                        Edit
                                    </a>
                                </Button>
                            </div>
                        </li>
                    ))}
                </ul>
            </section>

            <section className="grid gap-4 md:grid-cols-2">
                <div className="flex flex-col gap-3 rounded-xl border p-4">
                    <div className="flex items-center gap-2">
                        <PanelsTopLeft className="size-4 text-muted-foreground" />
                        <h2 className="font-medium">Shared areas</h2>
                    </div>
                    <p className="text-sm text-muted-foreground">
                        Shown on every page of the website.
                    </p>
                    <div className="flex flex-wrap gap-2">
                        {website.regions.map((region) =>
                            region.editUrl ? (
                                <Button
                                    key={region.name}
                                    variant="outline"
                                    size="sm"
                                    asChild
                                >
                                    <a href={region.editUrl}>{region.name}</a>
                                </Button>
                            ) : null,
                        )}
                    </div>
                </div>

                <div className="flex flex-col gap-3 rounded-xl border p-4">
                    <div className="flex items-center gap-2">
                        <Inbox className="size-4 text-muted-foreground" />
                        <h2 className="font-medium">Inbox</h2>
                        {website.unreadSubmissions > 0 ? (
                            <Badge>{website.unreadSubmissions} new</Badge>
                        ) : null}
                    </div>
                    <p className="text-sm text-muted-foreground">
                        Messages and sign-ups sent from your website's forms.
                    </p>
                    <div>
                        <Button variant="outline" size="sm" asChild>
                            <Link href={inbox(currentTeam?.slug ?? '')}>
                                Open inbox
                            </Link>
                        </Button>
                    </div>
                </div>
            </section>
        </>
    );
}

WebsiteShow.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Website',
            href: props.currentTeam ? show(props.currentTeam.slug) : '/',
        },
    ],
});
