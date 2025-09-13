<?php
/**
 * Breadcrumb Helper Class
 * Provides dynamic breadcrumb navigation for the election management system
 */
class BreadcrumbHelper {
    
    /**
     * Generate breadcrumb navigation based on current page
     */
    public static function generate($currentPage, $additionalItems = []) {
        $breadcrumbs = [];
        
        // Base navigation items
        $baseItems = [
            'mp' => [
                'url' => 'index.php',
                'label' => '📊 MP Master',
                'title' => 'Parliamentary Constituencies'
            ],
            'mla' => [
                'url' => 'mla_index.php', 
                'label' => '🏛️ MLA Master',
                'title' => 'Assembly Constituencies'
            ],
            'booth' => [
                'url' => 'booth_index.php',
                'label' => '🏛️ Booth Master', 
                'title' => 'Polling Booths'
            ]
        ];
        
        // Upload page mappings
        $uploadPages = [
            'upload.php' => 'mp',
            'mla_upload.php' => 'mla',
            'excel_upload_preview.php' => 'excel'
        ];
        
        // Determine the main section based on current page
        $mainSection = null;
        if (isset($uploadPages[$currentPage])) {
            $mainSection = $uploadPages[$currentPage];
        } elseif (strpos($currentPage, 'mla') !== false) {
            $mainSection = 'mla';
        } elseif (strpos($currentPage, 'booth') !== false) {
            $mainSection = 'booth';
        } else {
            $mainSection = 'mp';
        }
        
        // Build breadcrumb array
        $sections = ['mp', 'mla', 'booth'];
        $currentIndex = array_search($mainSection, $sections);
        
        // Add all sections up to current
        for ($i = 0; $i <= $currentIndex; $i++) {
            $section = $sections[$i];
            $isActive = ($i === $currentIndex && empty($additionalItems));
            
            $breadcrumbs[] = [
                'url' => $isActive ? null : $baseItems[$section]['url'],
                'label' => $baseItems[$section]['label'],
                'title' => $baseItems[$section]['title'],
                'active' => $isActive
            ];
        }
        
        // Add additional items (like upload pages)
        if (!empty($additionalItems)) {
            foreach ($additionalItems as $item) {
                $breadcrumbs[] = [
                    'url' => $item['url'] ?? null,
                    'label' => $item['label'],
                    'title' => $item['title'] ?? '',
                    'active' => $item['active'] ?? true
                ];
            }
        }
        
        return $breadcrumbs;
    }
    
    /**
     * Render breadcrumb HTML
     */
    public static function render($currentPage, $additionalItems = []) {
        $breadcrumbs = self::generate($currentPage, $additionalItems);
        
        $html = '<nav class="breadcrumb">';
        
        foreach ($breadcrumbs as $index => $item) {
            if ($index > 0) {
                $html .= '<span class="breadcrumb-separator">→</span>';
            }
            
            if ($item['active'] || $item['url'] === null) {
                $html .= '<span class="breadcrumb-item active" title="' . htmlspecialchars($item['title']) . '">' . 
                        htmlspecialchars($item['label']) . '</span>';
            } else {
                $html .= '<a href="' . htmlspecialchars($item['url']) . '" class="breadcrumb-item" title="' . 
                        htmlspecialchars($item['title']) . '">' . htmlspecialchars($item['label']) . '</a>';
            }
        }
        
        $html .= '</nav>';
        
        return $html;
    }
    
    /**
     * Get breadcrumb for specific pages
     */
    public static function getBreadcrumbForPage($page) {
        switch ($page) {
            case 'index.php':
                return self::render('index.php');
                
            case 'mla_index.php':
                return self::render('mla_index.php');
                
            case 'booth_index.php':
                return self::render('booth_index.php');
                
            case 'upload.php':
                return self::render('upload.php', [
                    [
                        'label' => '📤 Upload MP Data',
                        'title' => 'Upload Parliamentary Constituency Data'
                    ]
                ]);
                
            case 'mla_upload.php':
                return self::render('mla_upload.php', [
                    [
                        'label' => '📤 Upload MLA Data', 
                        'title' => 'Upload Assembly Constituency Data'
                    ]
                ]);
                
                
            case 'excel_upload_preview.php':
                return self::render('excel_upload_preview.php', [
                    [
                        'label' => '📤 Excel Upload & Preview',
                        'title' => 'Upload and Preview Excel/CSV Files'
                    ]
                ]);
                
            case 'user_management.php':
                return self::render('user_management.php', [
                    [
                        'label' => '👥 User Management',
                        'title' => 'Manage Users and Roles'
                    ]
                ]);
                
            case 'login.php':
                return self::render('login.php', [
                    [
                        'label' => '🔐 Login',
                        'title' => 'User Authentication'
                    ]
                ]);
                
            default:
                return self::render($page);
        }
    }
}
?>
