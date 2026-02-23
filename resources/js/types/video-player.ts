// TypeScript types for the Video Player

export interface User {
    id: number;
    name: string;
    email: string;
    role: 'analista' | 'entrenador' | 'jugador' | 'director_club' | 'super_admin';
    is_super_admin?: boolean;
    avatar?: string | null;
}

export interface Organization {
    id: number;
    name: string;
    slug: string;
    logo_path: string | null;
}

export interface Category {
    id: number;
    name: string;
    organization_id: number;
}

export interface Video {
    id: number;
    title: string;
    description: string | null;
    file_path: string;
    thumbnail_path: string | null;
    file_name: string;
    file_size: number;
    mime_type: string;
    duration: number | null;
    timeline_offset: number;
    uploaded_by: number;
    analyzed_team_name: string;
    rival_team_id: number | null;
    rival_team_name: string | null;
    rival_name: string | null;
    category_id: number;
    division: string | null;
    rugby_situation_id: number | null;
    match_date: string;
    status: string;
    visibility_type: string;
    processing_status: string;
    view_count: number;
    unique_viewers: number;
    created_at: string;
    updated_at: string;
    // Relationships
    category: Category | null;
    uploader: User;
    // Computed
    stream_url: string;
    edit_url: string;
    // Bunny Stream
    bunny_video_id: string | null;
    bunny_library_id: number | string | null;
    bunny_status: string | null;
    bunny_hls_url: string | null;
    bunny_mp4_url: string | null;
    // Multi-camera
    is_part_of_group: boolean;
    slave_videos: SlaveVideo[];
}

export interface SlaveVideo {
    id: number;
    title: string;
    stream_url: string;
    camera_angle: string;
    sync_offset: number;
    is_synced: boolean;
    bunny_hls_url?: string | null;
    bunny_status?: string | null;
    bunny_mp4_url?: string | null;
}

export interface VideoComment {
    id: number;
    video_id: number;
    user_id: number;
    parent_id: number | null;
    comment: string;
    timestamp_seconds: number;
    category: CommentCategory | null;
    priority: CommentPriority | null;
    status: string;
    created_at: string;
    updated_at: string;
    // Relationships
    user: User;
    replies: VideoComment[];
    mentioned_users: User[];
}

export type CommentCategory = 'tecnico' | 'tactico' | 'fisico' | 'mental';
export type CommentPriority = 'baja' | 'media' | 'alta' | 'critica';

export interface VideoClip {
    id: number;
    video_id: number;
    clip_category_id: number;
    organization_id: number;
    created_by: number;
    start_time: number;
    end_time: number;
    title: string | null;
    notes: string | null;
    players: string[] | null;
    tags: string[] | null;
    rating: number | null;
    is_highlight: boolean;
    is_shared: boolean;
    created_at: string;
    updated_at: string;
    // Relationships
    category?: ClipCategory;
    creator?: User;
}

export interface ClipCategory {
    id: number;
    organization_id: number;
    scope: 'organization' | 'user' | 'video';
    user_id: number | null;
    video_id: number | null;
    name: string;
    slug: string;
    color: string;
    icon: string | null;
    hotkey: string | null;
    lead_seconds: number;
    lag_seconds: number;
    sort_order: number;
    is_active: boolean;
    created_by: number | null;
}

export interface VideoAnnotation {
    id: number;
    video_id: number;
    user_id: number;
    timestamp: number;
    annotation_data: string; // JSON string of Fabric.js objects
    annotation_type: string;
    duration_seconds: number;
    is_permanent: boolean;
    is_visible: boolean;
    created_at: string;
    updated_at: string;
    // Relationships
    user?: User;
}

export interface VideoView {
    id: number;
    video_id: number;
    user_id: number;
    watched_percentage: number;
    total_watch_time: number;
    is_completed: boolean;
    created_at: string;
    user?: User;
}

export interface VideoStats {
    views: VideoView[];
    total_views: number;
    unique_viewers: number;
    average_watch_time: number;
}

// API Route helpers
export interface VideoPlayerRoutes {
    trackView: string;
    updateDuration: string;
    markCompleted: string;
    stats: string;
    clipCategories: string;
    clips: string;
    createClip: string;
    createCategory: string;
}

export interface VideoPlayerConfig {
    videoId: number;
    csrfToken: string;
    user: {
        id: number;
        name: string;
        role: string;
        canViewStats: boolean;
        canCreateClips: boolean;
    };
    routes: VideoPlayerRoutes;
}

// Page props passed from Inertia controller
export interface VideoShowProps {
    video: Video;
    comments: VideoComment[];
    allUsers: Pick<User, 'id' | 'name' | 'role'>[];
    config: VideoPlayerConfig;
}

// Toast types
export interface Toast {
    id: number;
    message: string;
    type: 'success' | 'error' | 'warning' | 'info';
    duration?: number;
}

// Speed control (extend up to 7x)
export type PlaybackSpeed =
    0.25 | 0.5 | 0.75 | 1 | 1.25 | 1.5 | 2 | 3 | 4 | 5 | 6 | 7;

export const PLAYBACK_SPEEDS: PlaybackSpeed[] = [
    0.25, 0.5, 0.75, 1, 1.25, 1.5, 2, 3, 4, 5, 6, 7,
];

// ── Lineup types ─────────────────────────────────────────────────────────────

export interface RivalPlayer {
    id: number;
    rival_team_id: number;
    name: string;
    shirt_number: number | null;
    usual_position: number | null;
    notes: string | null;
}

export interface LineupPlayer {
    id: number;
    lineup_id: number;
    user_id: number | null;
    rival_player_id: number | null;
    player_name: string | null;
    shirt_number: number | null;
    position_number: number | null;
    status: 'starter' | 'substitute' | 'unavailable';
    substitution_minute: number | null;
    display_name?: string;
    position_label?: string;
    // Relations
    user?: { id: number; name: string } | null;
    rival_player?: RivalPlayer | null;
}

export interface Lineup {
    id: number;
    video_id: number;
    team_type: 'local' | 'rival';
    formation: string | null;
    notes: string | null;
    players: LineupPlayer[];
}
