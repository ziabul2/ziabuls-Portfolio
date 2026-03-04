<?php
class SectionRenderer {
    private array $data;

    public function __construct(array $mainPortfolioData) {
        $this->data = $mainPortfolioData;
    }

    public function renderAll() {
        $sectionsFile = __DIR__ . '/../data/home_sections.json';
        if (!file_exists($sectionsFile)) {
            return;
        }

        $sections = json_decode(file_get_contents($sectionsFile), true);
        usort($sections, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

        foreach ($sections as $section) {
            if (!($section['visible'] ?? true)) continue;

            if ($section['type'] === 'builtin') {
                $this->renderBuiltin($section);
            } elseif ($section['type'] === 'dynamic_list') {
                $this->renderDynamicList($section);
            }
        }
    }

    private function renderBuiltin(array $section) {
        $includePath = __DIR__ . '/../' . $section['include_path'];
        if (file_exists($includePath)) {
            $data = $this->data; // Make $data available to included file
            include $includePath;
        }
    }

    private function renderDynamicList(array $section) {
        $dataFile = __DIR__ . '/../' . $section['data_file'];
        $items = [];
        if (file_exists($dataFile)) {
            $items = json_decode(file_get_contents($dataFile), true);
        }

        $title = $section['title'];
        $id = $section['id'];
        $icon = $section['icon'] ?? 'fas fa-list';

        include __DIR__ . '/../includes/dynamic_list_section.php';
    }
}
