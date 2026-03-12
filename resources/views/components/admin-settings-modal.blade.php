<!-- Admin Settings Modal -->
<div id="admin-modal-overlay" class="admin-modal-overlay">
    <div class="admin-modal">
        <!-- Modal Header -->
        <div class="admin-modal-header">
            <h2>⚙️ Admin Settings</h2>
            <button class="admin-modal-close" id="admin-modal-close">&times;</button>
        </div>
        
        <!-- Tabs -->
        <div class="admin-modal-tabs">
            <button class="admin-tab active" data-tab="configuration">Configuration</button>
            <button class="admin-tab" data-tab="users">User List</button>
            <button class="admin-tab" data-tab="email">Email (SMTP)</button>
            <button class="admin-tab" data-tab="sms">SMS (Infobip)</button>
        </div>
        
        <!-- Modal Content -->
        <div class="admin-modal-content">
            <!-- Alert Messages -->
            <div id="admin-alert" style="display: none;"></div>
            
            <!-- Tab 1: Configuration -->
            <div id="tab-configuration" class="admin-tab-pane active">
                <h3 style="margin-bottom: 20px; color: #2c3e50;">System Configuration</h3>
                <p style="color: #7f8c8d; margin-bottom: 20px;">
                    Configure general system settings and preferences.
                </p>
                
                <div class="admin-form-group">
                    <label>Application Name</label>
                    <input type="text" id="config-app-name" value="Family Tracker" placeholder="Enter application name">
                </div>
                
                <div class="admin-form-group">
                    <label>Default Map Center</label>
                    <p style="margin: 6px 0 12px; font-size: 12px; color: #6c757d;">
                        Click on the map below to set the default center location for the dashboard
                    </p>
                    
                    <!-- Interactive Map -->
                    <div id="admin-map" style="height: 300px; border: 1px solid #dee2e6; border-radius: 4px; margin-bottom: 12px;"></div>
                    
                    <!-- Coordinate Display -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="admin-form-group" style="margin-bottom: 0;">
                            <label>Latitude</label>
                            <input type="text" id="config-map-lat" value="21.4225" placeholder="21.4225" readonly>
                        </div>
                        
                        <div class="admin-form-group" style="margin-bottom: 0;">
                            <label>Longitude</label>
                            <input type="text" id="config-map-lng" value="39.8262" placeholder="39.8262" readonly>
                        </div>
                    </div>
                </div>
                
                <div class="admin-actions">
                    <button class="admin-btn admin-btn-primary" id="save-configuration">
                        <span>Save Configuration</span>
                    </button>
                </div>
            </div>
            
            <!-- Tab 2: User List -->
            <div id="tab-users" class="admin-tab-pane">
                <h3 style="margin-bottom: 20px; color: #2c3e50;">User Management</h3>
                
                <!-- Search and Filter -->
                <div class="admin-search-bar">
                    <input type="text" id="user-search" placeholder="Search by username or email...">
                    <select id="user-filter">
                        <option value="">All Users</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <button class="admin-btn admin-btn-primary" id="search-users">Search</button>
                </div>
                
                <!-- Users Table -->
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Devices</th>
                            <th>License Key</th>
                            <th>Status</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="users-table-body">
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: #7f8c8d;">
                                Loading users...
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <div class="admin-pagination" id="users-pagination">
                    <button id="users-prev" disabled>Previous</button>
                    <span id="users-page-info">Page 1 of 1</span>
                    <button id="users-next" disabled>Next</button>
                </div>
            </div>
            
            <!-- Tab 3: Email (SMTP) -->
            <div id="tab-email" class="admin-tab-pane">
                <h3 style="margin-bottom: 20px; color: #2c3e50;">Email Configuration (SMTP)</h3>
                <p style="color: #7f8c8d; margin-bottom: 20px;">
                    Configure SMTP settings for sending emails from the application.
                </p>
                
                <div class="admin-form-group">
                    <label>SMTP Host</label>
                    <input type="text" id="smtp-host" placeholder="smtp.example.com">
                </div>
                
                <div class="admin-form-row">
                    <div class="admin-form-group">
                        <label>SMTP Port</label>
                        <input type="text" id="smtp-port" placeholder="465">
                    </div>
                    
                    <div class="admin-form-group">
                        <label>Encryption</label>
                        <select id="smtp-encryption">
                            <option value="ssl">SSL</option>
                            <option value="tls">TLS</option>
                        </select>
                    </div>
                </div>
                
                <div class="admin-form-group">
                    <label>SMTP Username</label>
                    <input type="text" id="smtp-username" placeholder="user@example.com">
                </div>
                
                <div class="admin-form-group">
                    <label>SMTP Password</label>
                    <input type="password" id="smtp-password" placeholder="Enter SMTP password">
                </div>
                
                <div class="admin-form-group">
                    <label>Test Email Address</label>
                    <input type="email" id="test-email-address" placeholder="test@example.com">
                </div>
                
                <div class="admin-actions">
                    <button class="admin-btn admin-btn-success" id="test-email">
                        <span>Send Test Email</span>
                    </button>
                    <button class="admin-btn admin-btn-primary" id="save-email">
                        <span>Save Email Settings</span>
                    </button>
                </div>
            </div>
            
            <!-- Tab 4: SMS (Infobip) -->
            <div id="tab-sms" class="admin-tab-pane">
                <h3 style="margin-bottom: 20px; color: #2c3e50;">SMS Configuration (Infobip)</h3>
                <p style="color: #7f8c8d; margin-bottom: 20px;">
                    Configure Infobip API settings for sending SMS messages.
                </p>
                
                <div class="admin-form-group">
                    <label>Infobip API Key</label>
                    <input type="password" id="infobip-api-key" placeholder="Enter Infobip API key">
                </div>
                
                <div class="admin-form-group">
                    <label>Infobip Base URL</label>
                    <input type="text" id="infobip-base-url" placeholder="xxxxx.api.infobip.com">
                </div>
                
                <div class="admin-form-group">
                    <label>Sender Number</label>
                    <input type="text" id="infobip-sender-number" placeholder="+60123456789">
                    <p style="margin: 6px 0 0; font-size: 12px; color: #6c757d;">
                        The sender number registered with Infobip (include country code)
                    </p>
                </div>
                
                <div class="admin-form-group">
                    <label>Test Phone Number</label>
                    <input type="text" id="test-phone-number" placeholder="0123456789 or +60123456789">
                    <p style="margin: 6px 0 0; font-size: 12px; color: #6c757d;">
                        Enter Malaysian phone number (0xxx will be converted to +60xxx)
                    </p>
                </div>
                
                <div class="admin-actions">
                    <button class="admin-btn admin-btn-success" id="test-sms">
                        <span>Send Test SMS</span>
                    </button>
                    <button class="admin-btn admin-btn-primary" id="save-sms">
                        <span>Save SMS Settings</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
