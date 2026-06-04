<style>
    /* 1. Global Custom Theme Adjustments */
    :root {
        --primary-glow: radial-gradient(circle at 10% 20%, rgba(16, 185, 129, 0.05) 0%, transparent 40%),
                        radial-gradient(circle at 90% 80%, rgba(20, 184, 166, 0.05) 0%, transparent 40%);
    }

    .dark {
        --primary-glow: radial-gradient(circle at 10% 20%, rgba(16, 185, 129, 0.08) 0%, transparent 45%),
                        radial-gradient(circle at 90% 80%, rgba(20, 184, 166, 0.08) 0%, transparent 45%);
    }

    /* 2. Login Page Styling (Simple Layout) */
    .fi-simple-layout {
        background-image: var(--primary-glow) !important;
        background-color: #f8fafc !important;
        position: relative;
    }

    .dark .fi-simple-layout {
        background-color: #030712 !important; /* Extremely dark charcoal/black */
    }

    /* Modern floating card for login */
    .fi-simple-main-ctn {
        background: rgba(255, 255, 255, 0.7) !important;
        backdrop-filter: blur(16px) !important;
        -webkit-backdrop-filter: blur(16px) !important;
        border: 1px solid rgba(229, 231, 235, 0.6) !important;
        border-radius: 1.5rem !important;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.03), 0 10px 10px -5px rgba(0, 0, 0, 0.02) !important;
        padding: 2.5rem 2rem !important;
    }

    .dark .fi-simple-main-ctn {
        background: rgba(17, 24, 39, 0.5) !important;
        border: 1px solid rgba(55, 65, 81, 0.3) !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5) !important;
    }

    /* 3. Global Dashboard Enhancements */
    /* Sticky Topbar styled like frontend */
    .fi-topbar {
        background-color: rgba(255, 255, 255, 0.8) !important;
        backdrop-filter: blur(12px) !important;
        -webkit-backdrop-filter: blur(12px) !important;
        border-bottom: 1px solid rgba(241, 245, 249, 0.8) !important;
    }

    .dark .fi-topbar {
        background-color: rgba(3, 7, 18, 0.8) !important;
        border-bottom: 1px solid rgba(31, 41, 55, 0.5) !important;
    }

    /* Sidebar glassmorphic effect */
    .fi-sidebar {
        background-color: rgba(255, 255, 255, 0.5) !important;
        backdrop-filter: blur(16px) !important;
        -webkit-backdrop-filter: blur(16px) !important;
        border-right: 1px solid rgba(241, 245, 249, 0.8) !important;
    }

    .dark .fi-sidebar {
        background-color: rgba(3, 7, 18, 0.6) !important;
        border-right: 1px solid rgba(31, 41, 55, 0.5) !important;
    }

    /* 4. Sidebar Active Item Indicator */
    .fi-sidebar-item-active {
        position: relative;
    }

    .fi-sidebar-item-active a {
        background: linear-gradient(to right, rgba(16, 185, 129, 0.08), rgba(20, 184, 166, 0.01)) !important;
        border-left: 3px solid #10b981 !important;
        border-top-left-radius: 0px !important;
        border-bottom-left-radius: 0px !important;
        border-top-right-radius: 0.75rem !important;
        border-bottom-right-radius: 0.75rem !important;
        font-weight: 600 !important;
        color: #0f766e !important;
    }

    .dark .fi-sidebar-item-active a {
        background: linear-gradient(to right, rgba(16, 185, 129, 0.12), rgba(20, 184, 166, 0.02)) !important;
        border-left: 3px solid #10b981 !important;
        color: #34d399 !important;
    }

    /* 5. Modern Rounded Form Inputs */
    .fi-input-wrp {
        border-radius: 0.75rem !important;
        border-color: rgba(209, 213, 219, 0.6) !important;
        transition: all 0.2s ease-in-out !important;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02) !important;
    }

    .dark .fi-input-wrp {
        border-color: rgba(75, 85, 99, 0.4) !important;
        background-color: rgba(17, 24, 39, 0.3) !important;
    }

    .fi-input-wrp:focus-within {
        border-color: #10b981 !important;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15) !important;
    }

    /* 6. Buttons Styling */
    .fi-btn {
        border-radius: 9999px !important; /* Premium pill style button */
        font-weight: 600 !important;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }

    /* Lift buttons on hover */
    .fi-btn-color-primary {
        box-shadow: 0 4px 10px rgba(16, 185, 129, 0.15) !important;
    }
    
    .fi-btn-color-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 15px rgba(16, 185, 129, 0.25) !important;
    }

    .fi-btn-color-primary:active {
        transform: translateY(0);
    }

    /* 7. Dashboard Widgets, Tables & Sections */
    .fi-wi-widget, 
    .fi-section, 
    .fi-ta-ctn {
        border-radius: 1.25rem !important;
        border: 1px solid rgba(229, 231, 235, 0.6) !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.01), 0 2px 4px -1px rgba(0, 0, 0, 0.01) !important;
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .dark .fi-wi-widget, 
    .dark .fi-section, 
    .dark .fi-ta-ctn {
        border: 1px solid rgba(55, 65, 81, 0.3) !important;
        background-color: rgba(17, 24, 39, 0.2) !important;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
    }

    /* Subtle hover effect on widgets */
    .fi-wi-widget:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 20px -10px rgba(0, 0, 0, 0.04) !important;
    }
    
    .dark .fi-wi-widget:hover {
        box-shadow: 0 15px 30px -10px rgba(0, 0, 0, 0.3) !important;
    }

    /* Table visual refinements */
    .fi-ta-header {
        background-color: rgba(249, 250, 251, 0.6) !important;
        border-bottom: 1px solid rgba(229, 231, 235, 0.6) !important;
    }

    .dark .fi-ta-header {
        background-color: rgba(31, 41, 55, 0.2) !important;
        border-bottom: 1px solid rgba(55, 65, 81, 0.3) !important;
    }

    /* Table head cells styling */
    .fi-ta-header-cell {
        font-size: 0.75rem !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
        font-weight: 700 !important;
        color: #6b7280 !important;
    }

    .dark .fi-ta-header-cell {
        color: #9ca3af !important;
    }

    /* Beautiful focus and scrollbar refinements */
    ::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }

    ::-webkit-scrollbar-track {
        background: transparent;
    }

    ::-webkit-scrollbar-thumb {
        background: rgba(156, 163, 175, 0.3);
        border-radius: 9999px;
    }

    .dark ::-webkit-scrollbar-thumb {
        background: rgba(75, 85, 99, 0.3);
    }

    ::-webkit-scrollbar-thumb:hover {
        background: rgba(156, 163, 175, 0.5);
    }

    /* 8. Stats Overview Card Customizations */
    .fi-wi-stats-overview-stat {
        border-radius: 1.25rem !important;
        border: 1px solid rgba(229, 231, 235, 0.6) !important;
        background-color: #ffffff !important;
        transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.01), 0 2px 4px -1px rgba(0, 0, 0, 0.01) !important;
    }

    .dark .fi-wi-stats-overview-stat {
        border: 1px solid rgba(55, 65, 81, 0.3) !important;
        background-color: rgba(17, 24, 39, 0.2) !important;
    }

    .fi-wi-stats-overview-stat:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.05), 0 4px 6px -2px rgba(16, 185, 129, 0.05) !important;
    }

    .dark .fi-wi-stats-overview-stat:hover {
        box-shadow: 0 15px 25px -5px rgba(0, 0, 0, 0.3) !important;
    }
</style>
