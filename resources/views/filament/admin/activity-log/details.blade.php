@php
    $changes = $record->attribute_changes ?? $record->properties ?? [];
    $attributes = $changes['attributes'] ?? [];
    $old = $changes['old'] ?? [];
    
    // Collect all unique keys from attributes and old values
    $allKeys = array_unique(array_merge(array_keys($attributes), array_keys($old)));
    
    // Filter out internal fields we don't want to display
    $excludedFields = ['password', 'remember_token', 'updated_at', 'created_at', 'id', 'user_id', 'verified_by'];
    $allKeys = array_filter($allKeys, function ($key) use ($excludedFields) {
        return !in_array($key, $excludedFields);
    });

    // Helper function to format values nicely
    $formatValue = function ($value) {
        if (is_null($value)) {
            return null;
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        return (string) $value;
    };

    // Helper function to map field names to beautiful SVG icons
    $getFieldIcon = function ($field) {
        $field = strtolower($field);
        if (in_array($field, ['phase', 'block', 'house_number', 'street_number', 'house', 'street', 'address'])) {
            // Home icon
            return '<svg width="14" height="14" class="field-icon-svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>';
        }
        if (in_array($field, ['is_verified', 'status', 'rejection_reason', 'rejection_message', 'verified_at', 'flags_count', 'pinned'])) {
            // Shield check / lock icon
            return '<svg width="14" height="14" class="field-icon-svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>';
        }
        if (in_array($field, ['email', 'name', 'password', 'role', 'user_type', 'author_id'])) {
            // User icon
            return '<svg width="14" height="14" class="field-icon-svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>';
        }
        if (in_array($field, ['content', 'title', 'description', 'question', 'category'])) {
            // Document Text icon
            return '<svg width="14" height="14" class="field-icon-svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>';
        }
        if (in_array($field, ['price', 'amount', 'cost'])) {
            // Currency Dollar icon
            return '<svg width="14" height="14" class="field-icon-svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M12 16c1.22 0 2.22-.73 2.585-1.75"/></svg>';
        }
        if (in_array($field, ['phone_number', 'contact_whatsapp', 'whatsapp'])) {
            // Phone icon
            return '<svg width="14" height="14" class="field-icon-svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>';
        }
        if (in_array($field, ['start_at', 'end_at', 'expires_at', 'created_at', 'updated_at'])) {
            // Calendar icon
            return '<svg width="14" height="14" class="field-icon-svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>';
        }
        // Default Settings icon
        return '<svg width="14" height="14" class="field-icon-svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>';
    };

    // Helper function to format values beautifully as HTML badging
    $renderValue = function ($value) use ($formatValue) {
        $formatted = $formatValue($value);
        if (is_null($formatted)) {
            return '<span class="activity-value-null font-mono italic">null</span>';
        }
        if (is_bool($value)) {
            return $value 
                ? '<span class="activity-value-verified"><svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" style="display:inline-block; margin-right: 4px; shrink-0;"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>True</span>'
                : '<span class="activity-value-unverified"><svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" style="display:inline-block; margin-right: 4px; shrink-0;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>False</span>';
        }
        
        $valueStr = (string) $formatted;
        
        // Handle common status values
        if (in_array(strtolower($valueStr), ['active', 'approved', 'verified', 'published'])) {
            return '<span class="activity-value-status activity-value-status-emerald">' . ucfirst($valueStr) . '</span>';
        }
        if (in_array(strtolower($valueStr), ['pending', 'inactive', 'draft', 'suspended_pending'])) {
            return '<span class="activity-value-status activity-value-status-amber">' . ucfirst($valueStr) . '</span>';
        }
        if (in_array(strtolower($valueStr), ['suspended', 'rejected', 'banned', 'expired', 'deleted'])) {
            return '<span class="activity-value-status activity-value-status-rose">' . ucfirst($valueStr) . '</span>';
        }
        
        if (is_array($value) || is_object($value)) {
            return '<pre class="activity-value-json-pre">' . e($formatted) . '</pre>';
        }
        
        return '<span class="activity-value-display">' . e($valueStr) . '</span>';
    };
@endphp

<div class="activity-details-container">
    <style>
        .activity-details-container {
            font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
            color: #1e293b;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            padding: 0.5rem;
            line-height: 1.5;
        }
        
        .dark .activity-details-container {
            color: #e2e8f0;
        }
        
        /* Hero Banner */
        .activity-hero-banner {
            padding: 1.25rem;
            border-radius: 0.875rem;
            border: 1px solid #e2e8f0;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            display: flex;
            flex-direction: column;
            gap: 1rem;
            box-shadow: 0 1px 2px 0 rgba(0,0,0,0.02);
        }
        
        .dark .activity-hero-banner {
            border-color: #334155;
            background: linear-gradient(135deg, #0f172a, #1e293b);
        }
        
        @media (min-width: 768px) {
            .activity-hero-banner {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }
        }
        
        .activity-hero-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .activity-avatar {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ffffff;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }
        
        .dark .activity-avatar {
            border-color: #1e293b;
        }
        
        .activity-avatar-initials {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 50%;
            background: linear-gradient(135deg, #10b981, #059669);
            color: #ffffff;
            font-weight: 700;
            font-size: 1.15rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            flex-shrink: 0;
        }
        
        .activity-hero-right {
            display: flex;
            flex-direction: column;
            gap: 0.375rem;
            font-size: 0.75rem;
            color: #64748b;
        }
        
        .dark .activity-hero-right {
            color: #94a3b8;
        }
        
        @media (min-width: 768px) {
            .activity-hero-right {
                align-items: flex-end;
                text-align: right;
            }
        }
        
        /* Titles */
        .activity-title {
            font-size: 0.95rem;
            font-weight: 700;
            margin: 0;
            line-height: 1.25;
            color: #0f172a;
        }
        
        .dark .activity-title {
            color: #f8fafc;
        }
        
        .activity-subtitle {
            font-size: 0.75rem;
            color: #64748b;
            margin: 0.35rem 0 0 0;
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }
        
        .dark .activity-subtitle {
            color: #94a3b8;
        }
        
        /* Badges */
        .activity-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.15rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border: 1px solid transparent;
        }
        
        .activity-badge-created {
            background-color: #ecfdf5;
            color: #047857;
            border-color: #a7f3d0;
        }
        .dark .activity-badge-created {
            background-color: rgba(16, 185, 129, 0.1);
            color: #34d399;
            border-color: rgba(16, 185, 129, 0.2);
        }
        
        .activity-badge-updated {
            background-color: #fffbeb;
            color: #b45309;
            border-color: #fde68a;
        }
        .dark .activity-badge-updated {
            background-color: rgba(245, 158, 11, 0.1);
            color: #fbbf24;
            border-color: rgba(245, 158, 11, 0.2);
        }
        
        .activity-badge-deleted {
            background-color: #fff1f2;
            color: #be123c;
            border-color: #fecdd3;
        }
        .dark .activity-badge-deleted {
            background-color: rgba(239, 68, 68, 0.1);
            color: #f87171;
            border-color: rgba(239, 68, 68, 0.2);
        }
        
        .activity-badge-default {
            background-color: #eff6ff;
            color: #1d4ed8;
            border-color: #bfdbfe;
        }
        .dark .activity-badge-default {
            background-color: rgba(59, 130, 246, 0.1);
            color: #60a5fa;
            border-color: rgba(59, 130, 246, 0.2);
        }
        
        /* Pulsing Dot */
        .pulsing-dot-container {
            position: relative;
            display: inline-flex;
            height: 0.45rem;
            width: 0.45rem;
            margin-right: 0.375rem;
        }
        
        .pulsing-dot-ring {
            position: absolute;
            display: inline-flex;
            height: 100%;
            width: 100%;
            border-radius: 50%;
            opacity: 0.75;
            animation: ping 1s cubic-bezier(0, 0, 0.2, 1) infinite;
        }
        
        .pulsing-dot-center {
            position: relative;
            display: inline-flex;
            height: 0.45rem;
            width: 0.45rem;
            border-radius: 50%;
        }
        
        @keyframes ping {
            75%, 100% {
                transform: scale(2);
                opacity: 0;
            }
        }
        
        .created .pulsing-dot-ring, .created .pulsing-dot-center { background-color: #10b981; }
        .updated .pulsing-dot-ring, .updated .pulsing-dot-center { background-color: #f59e0b; }
        .deleted .pulsing-dot-ring, .deleted .pulsing-dot-center { background-color: #ef4444; }
        
        /* Code block */
        .activity-code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.725rem;
            padding: 0.15rem 0.375rem;
            border-radius: 0.25rem;
            background-color: #f1f5f9;
            border: 1px solid #e2e8f0;
            color: #334155;
            font-weight: 600;
        }
        
        .dark .activity-code {
            background-color: #0f172a;
            border-color: #334155;
            color: #cbd5e1;
        }
        
        /* Description Card */
        .activity-description-card {
            padding: 1rem;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            border-left-width: 4px;
            box-shadow: 0 1px 2px 0 rgba(0,0,0,0.01);
        }
        
        .dark .activity-description-card {
            border-color: #334155;
            background-color: rgba(30, 41, 59, 0.2);
        }
        
        .activity-description-card.created { border-left-color: #10b981; }
        .activity-description-card.updated { border-left-color: #f59e0b; }
        .activity-description-card.deleted { border-left-color: #ef4444; }
        
        .activity-section-label {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94a3b8;
            margin-bottom: 0.25rem;
            display: block;
        }
        
        .activity-description-text {
            font-size: 0.85rem;
            font-weight: 600;
            margin: 0;
            line-height: 1.5;
            color: #334155;
        }
        
        .dark .activity-description-text {
            color: #cbd5e1;
        }
        
        /* Field Changes */
        .activity-changes-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94a3b8;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 0.375rem;
            margin: 1.5rem 0 1rem 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .dark .activity-changes-label {
            border-color: #334155;
        }
        
        .activity-field-card {
            padding: 1rem;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            background-color: #ffffff;
            margin-bottom: 1rem;
            box-shadow: 0 1px 3px 0 rgba(0,0,0,0.03);
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .dark .activity-field-card {
            border-color: #334155;
            background-color: rgba(30, 41, 59, 0.4);
        }
        
        .activity-field-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 0.5rem;
        }
        
        .dark .activity-field-header {
            border-color: #334155;
        }
        
        .activity-field-name {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
            font-weight: 700;
            color: #475569;
            text-transform: capitalize;
        }
        
        .dark .activity-field-name {
            color: #94a3b8;
        }
        
        .field-icon-svg {
            display: inline-block;
            flex-shrink: 0;
        }
        
        .activity-field-badge {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            padding: 0.125rem 0.375rem;
            border-radius: 0.25rem;
        }
        
        .activity-field-badge-modified { background-color: #fef3c7; color: #d97706; }
        .activity-field-badge-added { background-color: #d1fae5; color: #059669; }
        .activity-field-badge-removed { background-color: #fee2e2; color: #dc2626; }
        .activity-field-badge-default { background-color: #f1f5f9; color: #475569; }
        
        /* Diff Panels */
        .activity-diff-grid {
            display: grid;
            grid-template-cols: 1fr;
            gap: 0.75rem;
        }
        
        @media (min-width: 768px) {
            .activity-diff-grid-split {
                grid-template-cols: 1fr 1fr;
            }
        }
        
        .activity-diff-pane {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .activity-diff-pane-title {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94a3b8;
        }
        
        .activity-value-box {
            padding: 0.75rem;
            border-radius: 0.5rem;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            display: flex;
            align-items: center;
            min-height: 2.25rem;
        }
        
        .dark .activity-value-box {
            border-color: #334155;
            background-color: #0f172a;
        }
        
        .activity-value-box-added {
            background-color: rgba(16, 185, 129, 0.03);
            border-color: rgba(16, 185, 129, 0.15);
        }
        .dark .activity-value-box-added {
            background-color: rgba(16, 185, 129, 0.05);
            border-color: rgba(16, 185, 129, 0.2);
        }
        
        .activity-value-box-removed {
            background-color: rgba(239, 68, 68, 0.03);
            border-color: rgba(239, 68, 68, 0.15);
        }
        .dark .activity-value-box-removed {
            background-color: rgba(239, 68, 68, 0.05);
            border-color: rgba(239, 68, 68, 0.2);
        }
        
        /* RenderValue output styling rules */
        .activity-value-null {
            color: #94a3b8;
            font-style: italic;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.75rem;
        }
        
        .activity-value-verified, .activity-value-unverified {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.15rem 0.625rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            border: 1px solid;
        }
        
        .activity-value-verified {
            background-color: #f0fdf4;
            color: #166534;
            border-color: #bbf7d0;
        }
        .dark .activity-value-verified {
            background-color: rgba(16, 185, 129, 0.1);
            color: #34d399;
            border-color: rgba(16, 185, 129, 0.2);
        }
        
        .activity-value-unverified {
            background-color: #fef2f2;
            color: #991b1b;
            border-color: #fecaca;
        }
        .dark .activity-value-unverified {
            background-color: rgba(239, 68, 68, 0.1);
            color: #f87171;
            border-color: rgba(239, 68, 68, 0.2);
        }
        
        .activity-value-status {
            display: inline-flex;
            align-items: center;
            padding: 0.15rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.725rem;
            font-weight: 700;
            border: 1px solid;
        }
        
        .activity-value-status-emerald {
            background-color: #f0fdf4;
            color: #166534;
            border-color: #bbf7d0;
        }
        .dark .activity-value-status-emerald {
            background-color: rgba(16, 185, 129, 0.1);
            color: #34d399;
            border-color: rgba(16, 185, 129, 0.2);
        }
        
        .activity-value-status-amber {
            background-color: #fffbeb;
            color: #92400e;
            border-color: #fde68a;
        }
        .dark .activity-value-status-amber {
            background-color: rgba(245, 158, 11, 0.1);
            color: #fbbf24;
            border-color: rgba(245, 158, 11, 0.2);
        }
        
        .activity-value-status-rose {
            background-color: #fef2f2;
            color: #991b1b;
            border-color: #fecaca;
        }
        .dark .activity-value-status-rose {
            background-color: rgba(239, 68, 68, 0.1);
            color: #f87171;
            border-color: rgba(239, 68, 68, 0.2);
        }
        
        .activity-value-json-pre {
            margin: 0;
            padding: 0.75rem;
            border-radius: 0.5rem;
            background-color: #0f172a;
            color: #f1f5f9;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.7rem;
            line-height: 1.5;
            overflow-x: auto;
            box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06);
            width: 100%;
            border: 1px solid #1e293b;
        }
        
        .dark .activity-value-json-pre {
            background-color: #020617;
            color: #cbd5e1;
            border-color: #1e293b;
        }
        
        .activity-value-display {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.75rem;
            word-break: break-all;
            line-height: 1.5;
            color: #1e293b;
        }
        
        .dark .activity-value-display {
            color: #e2e8f0;
        }
        
        .activity-value-box-removed .activity-value-display {
            text-decoration: line-through;
            color: #991b1b;
        }
        .dark .activity-value-box-removed .activity-value-display {
            color: #f87171;
        }
        
        .activity-value-box-added .activity-value-display {
            font-weight: 600;
            color: #15803d;
        }
        .dark .activity-value-box-added .activity-value-display {
            color: #34d399;
        }
        
        /* Empty State */
        .activity-empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2.5rem;
            text-align: center;
            border-radius: 1rem;
            border: 2px dashed #cbd5e1;
            background-color: rgba(248, 250, 252, 0.5);
        }
        
        .dark .activity-empty-state {
            border-color: #475569;
            background-color: rgba(15, 23, 42, 0.1);
        }
        
        .activity-empty-title {
            font-size: 0.875rem;
            font-weight: 700;
            margin-top: 0.75rem;
            color: #1e293b;
        }
        
        .dark .activity-empty-title {
            color: #f8fafc;
        }
        
        .activity-empty-description {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.25rem;
            max-width: 250px;
        }
    </style>
    
    <!-- Hero Activity summary Banner -->
    <div class="activity-hero-banner">
        <div class="activity-hero-left">
            @if($record->causer && $record->causer->avatar_url)
                <img src="{{ $record->causer->avatar_url }}" class="activity-avatar" alt="Avatar">
            @else
                <div class="activity-avatar-initials">
                    {{ substr($record->causer?->name ?? 'System', 0, 1) }}
                </div>
            @endif
            <div>
                <h3 class="activity-title">
                    {{ $record->causer?->name ?? 'System' }}
                </h3>
                <p class="activity-subtitle">
                    <span class="activity-badge @if($record->event === 'created') activity-badge-created @elseif($record->event === 'updated') activity-badge-updated @elseif($record->event === 'deleted') activity-badge-deleted @else activity-badge-default @endif">
                        <span class="pulsing-dot-container {{ $record->event }}">
                            <span class="pulsing-dot-ring"></span>
                            <span class="pulsing-dot-center"></span>
                        </span>
                        {{ $record->event }}
                    </span>
                </p>
            </div>
        </div>
        
        <div class="activity-hero-right">
            <div>
                <span class="activity-section-label" style="display:inline-block; margin-bottom:0; margin-right:4px;">Resource:</span>
                <span class="activity-code">{{ class_basename($record->subject_type ?? 'N/A') }}</span>
            </div>
            <div style="margin-top: 4px;">
                <span class="activity-section-label" style="display:inline-block; margin-bottom:0; margin-right:4px;">Target ID:</span>
                <span class="activity-code" style="user-select: all;">{{ $record->subject_id ?? 'N/A' }}</span>
            </div>
        </div>
    </div>

    <!-- Description / Message -->
    <div class="activity-description-card {{ $record->event }}">
        <span class="activity-section-label">Audit Trail Statement</span>
        <blockquote class="activity-description-text">
            "{{ $record->description }}"
        </blockquote>
        <span class="activity-subtitle" style="margin-top: 6px; font-weight: normal;">
            <svg width="12" height="12" class="opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="display:inline-block; margin-right: 4px; shrink-0;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ $record->created_at->format('l, M d, Y - h:i A') }} ({{ $record->created_at->diffForHumans() }})
        </span>
    </div>

    <!-- Field modifications layout -->
    <div>
        <div class="activity-changes-label">
            <span>Property Modifications</span>
            <span style="font-weight: normal; text-transform: none; font-size: 0.7rem;">{{ count($allKeys) }} fields recorded</span>
        </div>
        
        @if(count($allKeys) > 0)
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                @foreach($allKeys as $key)
                    @php
                        $oldVal = $old[$key] ?? null;
                        $newVal = $attributes[$key] ?? null;
                        $isChanged = ($oldVal !== $newVal) && (count($old) > 0) && (count($attributes) > 0);
                    @endphp

                    <div class="activity-field-card">
                        <div class="activity-field-header">
                            <div class="activity-field-name">
                                {!! $getFieldIcon($key) !!}
                                <span>{{ str_replace('_', ' ', $key) }}</span>
                            </div>
                            
                            @if($record->event === 'updated' && $isChanged)
                                <span class="activity-field-badge activity-field-badge-modified">Modified</span>
                            @elseif($record->event === 'created')
                                <span class="activity-field-badge activity-field-badge-added">Added</span>
                            @elseif($record->event === 'deleted')
                                <span class="activity-field-badge activity-field-badge-removed">Removed</span>
                            @else
                                <span class="activity-field-badge activity-field-badge-default">No Change</span>
                            @endif
                        </div>

                        @if($record->event === 'created')
                            <!-- Created Layout -->
                            <div class="activity-diff-pane">
                                <span class="activity-diff-pane-title">New Value</span>
                                <div class="activity-value-box activity-value-box-added">
                                    {!! $renderValue($newVal) !!}
                                </div>
                            </div>
                        @elseif($record->event === 'deleted')
                            <!-- Deleted Layout -->
                            <div class="activity-diff-pane">
                                <span class="activity-diff-pane-title">Removed Value</span>
                                <div class="activity-value-box activity-value-box-removed">
                                    {!! $renderValue($oldVal) !!}
                                </div>
                            </div>
                        @else
                            <!-- Updated Layout -->
                            <div class="activity-diff-grid activity-diff-grid-split">
                                <div class="activity-diff-pane">
                                    <span class="activity-diff-pane-title">Original Value</span>
                                    <div class="activity-value-box @if($isChanged) activity-value-box-removed @endif">
                                        {!! $renderValue($oldVal) !!}
                                    </div>
                                </div>
                                
                                <div class="activity-diff-pane">
                                    <span class="activity-diff-pane-title">Updated Value</span>
                                    <div class="activity-value-box @if($isChanged) activity-value-box-added @endif">
                                        {!! $renderValue($newVal) !!}
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="activity-empty-state">
                <svg width="40" height="40" style="color: #94a3b8;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                </svg>
                <h4 class="activity-empty-title">No properties changed</h4>
                <p class="activity-empty-description">This event did not record any modifications to individual model attributes.</p>
            </div>
        @endif
    </div>
</div>
