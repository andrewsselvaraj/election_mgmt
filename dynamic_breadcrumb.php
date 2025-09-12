<?php
require_once 'config.php';

/**
 * Dynamic Breadcrumb System
 * Creates data-driven breadcrumb navigation based on actual database relationships
 */
class DynamicBreadcrumb {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Generate dynamic breadcrumb based on current context and data
     */
    public function generate($currentPage, $params = []) {
        $breadcrumbs = [];
        
        // Always start with MP Master
        $breadcrumbs[] = [
            'url' => 'index.php',
            'label' => '📊 MP Master',
            'title' => 'Parliamentary Constituencies',
            'active' => false
        ];
        
        // Check if we have MP context
        if (isset($params['mp_id']) && !empty($params['mp_id'])) {
            $mpData = $this->getMPData($params['mp_id']);
            if ($mpData) {
                $breadcrumbs[] = [
                    'url' => 'mp_detail.php?mp_id=' . $params['mp_id'],
                    'label' => '📊 ' . $mpData['mp_constituency_name'],
                    'title' => 'MP: ' . $mpData['mp_constituency_name'] . ' (' . $mpData['state'] . ')',
                    'active' => false
                ];
                
                // Check if we have MLA context
                if (isset($params['mla_id']) && !empty($params['mla_id'])) {
                    $mlaData = $this->getMLAData($params['mla_id']);
                    if ($mlaData) {
                        $breadcrumbs[] = [
                            'url' => 'mla_detail.php?mp_id=' . $params['mp_id'] . '&mla_id=' . $params['mla_id'],
                            'label' => '🏛️ ' . $mlaData['mla_constituency_name'],
                            'title' => 'MLA: ' . $mlaData['mla_constituency_name'],
                            'active' => false
                        ];
                        
                        // Check if we have Booth context
                        if (isset($params['booth_id']) && !empty($params['booth_id'])) {
                            $boothData = $this->getBoothData($params['booth_id']);
                            if ($boothData) {
                                $breadcrumbs[] = [
                                    'url' => null,
                                    'label' => '🏛️ ' . $boothData['polling_station_no'],
                                    'title' => 'Booth: ' . $boothData['polling_station_no'] . ' - ' . $boothData['location_name_of_building'],
                                    'active' => true
                                ];
                            }
                        } else {
                            // MLA level - show booths
                            $breadcrumbs[] = [
                                'url' => null,
                                'label' => '🏛️ MLA Booths',
                                'title' => 'Booths in ' . $mlaData['mla_constituency_name'],
                                'active' => true
                            ];
                        }
                    }
                } else {
                    // MP level - show MLAs
                    $breadcrumbs[] = [
                        'url' => null,
                        'label' => '🏛️ MLA Constituencies',
                        'title' => 'MLAs in ' . $mpData['mp_constituency_name'],
                        'active' => true
                    ];
                }
            }
        } else {
            // Top level - show MPs
            $breadcrumbs[] = [
                'url' => null,
                'label' => '📊 All MPs',
                'title' => 'All Parliamentary Constituencies',
                'active' => true
            ];
        }
        
        return $breadcrumbs;
    }
    
    /**
     * Render breadcrumb HTML
     */
    public function render($currentPage, $params = []) {
        $breadcrumbs = $this->generate($currentPage, $params);
        
        $html = '<nav class="breadcrumb dynamic-breadcrumb">';
        
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
     * Get MP data by ID
     */
    private function getMPData($mpId) {
        try {
            $sql = "SELECT * FROM mp_master WHERE mp_id = :mp_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':mp_id', $mpId);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Get MLA data by ID
     */
    private function getMLAData($mlaId) {
        try {
            $sql = "SELECT * FROM mla_master WHERE mla_id = :mla_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':mla_id', $mlaId);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Get Booth data by ID
     */
    private function getBoothData($boothId) {
        try {
            $sql = "SELECT * FROM booth_master WHERE booth_id = :booth_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':booth_id', $boothId);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Get breadcrumb for specific pages with context
     */
    public function getBreadcrumbForPage($page, $params = []) {
        // Extract context from URL parameters
        $context = [];
        if (isset($_GET['mp_id'])) {
            $context['mp_id'] = $_GET['mp_id'];
        }
        if (isset($_GET['mla_id'])) {
            $context['mla_id'] = $_GET['mla_id'];
        }
        if (isset($_GET['booth_id'])) {
            $context['booth_id'] = $_GET['booth_id'];
        }
        
        // Merge with provided params
        $context = array_merge($context, $params);
        
        return $this->render($page, $context);
    }
}
?>
