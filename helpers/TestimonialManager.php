<?php
/**
 * TestimonialManager - Manage client feedback
 */
class TestimonialManager {
    private $dataFile;

    public function __construct() {
        $this->dataFile = __DIR__ . '/../data/testimonials.json';
        if (!file_exists($this->dataFile)) {
            file_put_contents($this->dataFile, json_encode([], JSON_PRETTY_PRINT));
        }
    }

    public function getTestimonials() {
        return json_decode(file_get_contents($this->dataFile), true) ?: [];
    }

    public function saveTestimonials($data) {
        return file_put_contents($this->dataFile, json_encode($data, JSON_PRETTY_PRINT)) !== false;
    }

    public function addTestimonial($testimonial) {
        $data = $this->getTestimonials();
        $testimonial['id'] = 'test_' . uniqid();
        $testimonial['created_at'] = time();
        $data[] = $testimonial;
        return $this->saveTestimonials($data);
    }

    public function deleteTestimonial($id) {
        $data = $this->getTestimonials();
        $data = array_filter($data, function($item) use ($id) {
            return $item['id'] !== $id;
        });
        return $this->saveTestimonials(array_values($data));
    }
}
