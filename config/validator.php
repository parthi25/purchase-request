<?php
/**
 * Input Validation Service
 * Provides comprehensive validation for all input types
 */

class Validator {
    
    private $errors = [];
    
    /**
     * Validate Purchase Request data
     */
    public function validatePR($data) {
        $this->errors = [];
        
        // Required fields
        $requiredFields = ['supplier_id', 'category', 'qty', 'uom'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $this->errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }
        
        // Validate supplier
        if (!empty($data['supplier_id'])) {
            if ($data['supplier_id'] !== 'NEW SUPPLIER' && !Security::validateInteger($data['supplier_id'], 1)) {
                $this->errors[] = 'Invalid supplier selection';
            }
        }
        
        // Validate quantity
        if (!empty($data['qty'])) {
            if (!Security::validateInteger($data['qty'], 1, 999999)) {
                $this->errors[] = 'Quantity must be a positive integer between 1 and 999999';
            }
        }
        
        // Validate UOM
        if (!empty($data['uom'])) {
            if (!Security::validateString($data['uom'], 1, 50, '/^[a-zA-Z0-9\s\-\.]+$/')) {
                $this->errors[] = 'UOM must be 1-50 characters and contain only letters, numbers, spaces, hyphens, and dots';
            }
        }
        
        // Validate category
        if (!empty($data['category'])) {
            if (!Security::validateString($data['category'], 1, 100, '/^[a-zA-Z0-9\s\-\.]+$/')) {
                $this->errors[] = 'Category must be 1-100 characters and contain only letters, numbers, spaces, hyphens, and dots';
            }
        }
        
        // Validate purchase type
        if (!empty($data['purchtype'])) {
            if (!Security::validateInteger($data['purchtype'], 1)) {
                $this->errors[] = 'Invalid purchase type selection';
            }
        }
        
        // Validate remarks (optional but if provided, validate)
        if (!empty($data['remark'])) {
            if (!Security::validateString($data['remark'], 0, 1000)) {
                $this->errors[] = 'Remarks must be less than 1000 characters';
            }
        }
        
        // Validate buyer (if provided)
        if (!empty($data['buyer'])) {
            if (!Security::validateInteger($data['buyer'], 1)) {
                $this->errors[] = 'Invalid buyer selection';
            }
        }
        
        return empty($this->errors);
    }
    
    /**
     * Validate status update data
     */
    public function validateStatusUpdate($data) {
        $this->errors = [];
        
        // Required fields
        if (empty($data['ids']) || empty($data['status'])) {
            $this->errors[] = 'IDs and status are required';
        }
        
        // Validate IDs
        if (!empty($data['ids'])) {
            $ids = is_array($data['ids']) ? $data['ids'] : [$data['ids']];
            foreach ($ids as $id) {
                if (!Security::validateInteger($id, 1)) {
                    $this->errors[] = 'Invalid ID provided';
                    break;
                }
            }
        }
        
        // Validate status
        if (!empty($data['status'])) {
            $validStatuses = [1, 2, 3, 4, 5, 6, 7, 8, 9];
            if (!in_array((int)$data['status'], $validStatuses)) {
                $this->errors[] = 'Invalid status value';
            }
        }
        
        // Validate optional fields
        if (!empty($data['buyerInput'])) {
            if (!Security::validateInteger($data['buyerInput'], 1)) {
                $this->errors[] = 'Invalid buyer selection';
            }
        }
        
        if (!empty($data['poHeadInput'])) {
            if (!Security::validateInteger($data['poHeadInput'], 1)) {
                $this->errors[] = 'Invalid PO head selection';
            }
        }
        
        if (!empty($data['qtyInput'])) {
            if (!Security::validateInteger($data['qtyInput'], 1, 999999)) {
                $this->errors[] = 'Invalid quantity';
            }
        }
        
        if (!empty($data['remarkInput'])) {
            if (!Security::validateString($data['remarkInput'], 0, 1000)) {
                $this->errors[] = 'Remarks must be less than 1000 characters';
            }
        }
        
        return empty($this->errors);
    }
    
    /**
     * Validate file upload data
     */
    public function validateFileUpload($data) {
        $this->errors = [];
        
        // Required fields
        if (empty($data['id']) || empty($data['type'])) {
            $this->errors[] = 'ID and type are required';
        }
        
        // Validate ID
        if (!empty($data['id'])) {
            if (!Security::validateInteger($data['id'], 1)) {
                $this->errors[] = 'Invalid ID provided';
            }
        }
        
        // Validate type
        if (!empty($data['type'])) {
            $validTypes = ['proforma', 'po', 'product'];
            if (!in_array($data['type'], $validTypes)) {
                $this->errors[] = 'Invalid file type. Must be: proforma, po, or product';
            }
        }
        
        return empty($this->errors);
    }
    
    /**
     * Validate search parameters
     */
    public function validateSearchParams($data) {
        $this->errors = [];
        
        // Validate search term
        if (!empty($data['search'])) {
            if (!Security::validateString($data['search'], 0, 255)) {
                $this->errors[] = 'Search term must be less than 255 characters';
            }
        }
        
        // Validate date range
        if (!empty($data['from_date'])) {
            if (!Security::validateDate($data['from_date'])) {
                $this->errors[] = 'Invalid from date format';
            }
        }
        
        if (!empty($data['to_date'])) {
            if (!Security::validateDate($data['to_date'])) {
                $this->errors[] = 'Invalid to date format';
            }
        }
        
        // Validate pagination
        if (!empty($data['offset'])) {
            if (!Security::validateInteger($data['offset'], 0)) {
                $this->errors[] = 'Invalid offset value';
            }
        }
        
        if (!empty($data['limit'])) {
            if (!Security::validateInteger($data['limit'], 1, 100)) {
                $this->errors[] = 'Limit must be between 1 and 100';
            }
        }
        
        // Validate status filter
        if (!empty($data['status'])) {
            if (!Security::validateInteger($data['status'], 1, 9)) {
                $this->errors[] = 'Invalid status filter';
            }
        }
        
        return empty($this->errors);
    }
    
    /**
     * Validate login data
     */
    public function validateLogin($data) {
        $this->errors = [];
        
        // Required fields
        if (empty($data['username'])) {
            $this->errors[] = 'Username is required';
        }
        
        if (empty($data['password'])) {
            $this->errors[] = 'Password is required';
        }
        
        // Validate username
        if (!empty($data['username'])) {
            if (!Security::validateString($data['username'], 3, 50, '/^[a-zA-Z0-9_\-\.]+$/')) {
                $this->errors[] = 'Username must be 3-50 characters and contain only letters, numbers, underscores, hyphens, and dots';
            }
        }
        
        // Validate password
        // if (!empty($data['password'])) {
        //     if (!Security::validateString($data['password'], 6, 255)) {
        //         $this->errors[] = 'Password must be at least 6 characters';
        //     }
        // }
        
        return empty($this->errors);
    }
    
    /**
     * Validate new supplier data
     */
    public function validateNewSupplier($data) {
        $this->errors = [];
        
        // Required fields
        $requiredFields = ['supplier', 'city', 'agent'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $this->errors[] = ucfirst($field) . ' is required';
            }
        }
        
        // Validate supplier name
        if (!empty($data['supplier'])) {
            if (!Security::validateString($data['supplier'], 1, 255, '/^[a-zA-Z0-9\s\-\.&,()]+$/')) {
                $this->errors[] = 'Supplier name contains invalid characters';
            }
        }
        
        // Validate city
        if (!empty($data['city'])) {
            if (!Security::validateString($data['city'], 1, 100, '/^[a-zA-Z\s\-\.]+$/')) {
                $this->errors[] = 'City name contains invalid characters';
            }
        }
        
        // Validate agent
        if (!empty($data['agent'])) {
            if (!Security::validateString($data['agent'], 1, 255, '/^[a-zA-Z0-9\s\-\.&,()]+$/')) {
                $this->errors[] = 'Agent name contains invalid characters';
            }
        }
        
        return empty($this->errors);
    }
    
    /**
     * Get validation errors
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Get first error message
     */
    public function getFirstError() {
        return !empty($this->errors) ? $this->errors[0] : null;
    }
    
    /**
     * Check if validation passed
     */
    public function isValid() {
        return empty($this->errors);
    }
}
?>
