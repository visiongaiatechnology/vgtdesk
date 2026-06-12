<?php
/**
 * Template part: Task Manager Window
 * STATUS: 💠 DIAMANT VGT SUPREME
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
                <!-- NATIVES TASK-MANAGER-FENSTER -->
                <div id="win-task-manager" class="window hidden absolute vgt-window" style="width: 820px; height: 550px; top: 15%; left: 30%; z-index: 101;" onclick="VGTDeskEngine.focusWindow('task-manager')">
                    
                    <!-- Resize Handles -->
                    <div class="resize-handle resize-handle-n" onmousedown="VGTDeskEngine.startResize(event, 'task-manager', 'n')"></div>
                    <div class="resize-handle resize-handle-s" onmousedown="VGTDeskEngine.startResize(event, 'task-manager', 's')"></div>
                    <div class="resize-handle resize-handle-e" onmousedown="VGTDeskEngine.startResize(event, 'task-manager', 'e')"></div>
                    <div class="resize-handle resize-handle-w" onmousedown="VGTDeskEngine.startResize(event, 'task-manager', 'w')"></div>
                    <div class="resize-handle resize-handle-nw" onmousedown="VGTDeskEngine.startResize(event, 'task-manager', 'nw')"></div>
                    <div class="resize-handle resize-handle-ne" onmousedown="VGTDeskEngine.startResize(event, 'task-manager', 'ne')"></div>
                    <div class="resize-handle resize-handle-sw" onmousedown="VGTDeskEngine.startResize(event, 'task-manager', 'sw')"></div>
                    <div class="resize-handle resize-handle-se" onmousedown="VGTDeskEngine.startResize(event, 'task-manager', 'se')"></div>

                    <!-- Titlebar -->
                    <div class="vgt-window-header cursor-move window-header">
                        <div class="vgt-window-dots">
                            <span class="vgt-window-dot dot-rose" onclick="VGTDeskEngine.closeWindow('task-manager')"></span>
                            <span class="vgt-window-dot dot-amber" onclick="VGTDeskEngine.minimizeWindow('task-manager')"></span>
                            <span class="vgt-window-dot dot-emerald" onclick="VGTDeskEngine.maximizeWindow('task-manager')"></span>
                        </div>
                        <span class="vgt-window-title" id="taskmanager-title-accent">VGT Task-Manager & Cron-Orchestrator</span>
                        <div class="vgt-window-spacer"></div>
                    </div>
                    <!-- Body Content -->
                    <div class="vgt-window-body vgt-cc-container">
                        <!-- Left Navigation Sidebar -->
                        <div class="vgt-cc-sidebar">
                            <div class="vgt-cc-nav">
                                <button class="vgt-tm-nav-item active vgt-cc-nav-item" data-tab="crons" onclick="VGTDeskEngine.switchTMTab('crons')">
                                    <span class="vgt-cc-nav-icon">⏰</span>
                                    <span class="vgt-cc-nav-text">Cron-Schedules</span>
                                </button>
                                <button class="vgt-tm-nav-item vgt-cc-nav-item" data-tab="transients" onclick="VGTDeskEngine.switchTMTab('transients')">
                                    <span class="vgt-cc-nav-icon">💾</span>
                                    <span class="vgt-cc-nav-text">Transients</span>
                                </button>
                                <button class="vgt-tm-nav-item vgt-cc-nav-item" data-tab="workers" onclick="VGTDeskEngine.switchTMTab('workers')">
                                    <span class="vgt-cc-nav-icon">⚙️</span>
                                    <span class="vgt-cc-nav-text">AJAX-Worker</span>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Right Panel Content -->
                        <div class="vgt-cc-content">
                            <!-- Tab: Crons -->
                            <div id="vgt-tm-tab-crons" class="vgt-tm-tab-panel active vgt-cc-tab-panel">
                                <h3 class="vgt-cc-section-title">WordPress Cron-Schedules</h3>
                                <p class="vgt-cc-section-desc">Aktive registrierte Hintergrund-Crons und deren Ausführungszeiten. Hängende Jobs können gelöscht werden.</p>
                                <div class="vgt-cc-table-wrapper" style="max-height: 340px; overflow-y: auto;">
                                    <table class="vgt-cc-table">
                                        <thead>
                                            <tr>
                                                <th>Hook-Name</th>
                                                <th>Nächster Start</th>
                                                <th>Intervall</th>
                                                <th>Aktion</th>
                                            </tr>
                                        </thead>
                                        <tbody id="vgt-tm-crons-table-body">
                                            <tr>
                                                <td colspan="4" style="text-align: center; color: #64748b;">Lade Cron-Schedules...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Tab: Transients -->
                            <div id="vgt-tm-tab-transients" class="vgt-tm-tab-panel vgt-cc-tab-panel">
                                <h3 class="vgt-cc-section-title">Temporäre Datenbank-Transients</h3>
                                <p class="vgt-cc-section-desc">Aktive transiente Tasks und gecachte Daten in der Options-Tabelle. "Kill" bereinigt sie sofort.</p>
                                <div class="vgt-cc-table-wrapper" style="max-height: 340px; overflow-y: auto;">
                                    <table class="vgt-cc-table">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Option-Key</th>
                                                <th>Aktion</th>
                                            </tr>
                                        </thead>
                                        <tbody id="vgt-tm-transients-table-body">
                                            <tr>
                                                <td colspan="3" style="text-align: center; color: #64748b;">Lade Transients...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Tab: Workers -->
                            <div id="vgt-tm-tab-workers" class="vgt-tm-tab-panel vgt-cc-tab-panel">
                                <h3 class="vgt-cc-section-title">Laufzeitbelegung asynchroner AJAX-Worker</h3>
                                <p class="vgt-cc-section-desc">Überwachung der aktiven Backend-Prozesse und CPU-/RAM-Zuweisung.</p>
                                <div class="vgt-cc-table-wrapper" style="max-height: 340px; overflow-y: auto;">
                                    <table class="vgt-cc-table">
                                        <thead>
                                            <tr>
                                                <th>PID</th>
                                                <th>Worker-Typ</th>
                                                <th>Laufzeit</th>
                                                <th>RAM</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="vgt-tm-workers-table-body">
                                            <tr>
                                                <td colspan="5" style="text-align: center; color: #64748b;">Lade AJAX-Worker...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
