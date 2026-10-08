export type WebsiteFamily = {
    value: string;
    label: string;
    description: string;
};

export type WebsitePage = {
    id: number;
    name: string;
    path: string;
    isHomepage: boolean;
    isPublished: boolean;
    updatedAt: string | null;
    editUrl: string;
    url: string | null;
};

export type WebsiteRegion = {
    name: string;
    editUrl: string | null;
};

export type Website = {
    name: string;
    status: 'draft' | 'live' | 'archived';
    statusLabel: string;
    isLive: boolean;
    url: string;
    unreadSubmissions: number;
    regions: WebsiteRegion[];
    pages: WebsitePage[];
};

export type WebsiteSubmission = {
    id: number;
    type: 'contact' | 'newsletter';
    typeLabel: string;
    sender: string;
    email: string | null;
    fields: Record<string, string>;
    isRead: boolean;
    receivedAt: string | null;
};
