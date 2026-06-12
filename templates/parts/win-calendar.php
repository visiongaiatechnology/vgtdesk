<?php
/**
 * Template part: Calendar Window
 * STATUS: 💠 DIAMANT VGT SUPREME
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
                <!-- NATIVES KALENDER-FENSTER -->
                <div id="win-calendar" class="window hidden absolute vgt-window" style="width: 360px; height: 410px; top: 10%; right: 14px; z-index: 101;" onclick="VGTDeskEngine.focusWindow('calendar')">
                    
                    <!-- 8 Resize Handles -->
                    <div class="resize-handle resize-handle-n" onmousedown="VGTDeskEngine.startResize(event, 'calendar', 'n')"></div>
                    <div class="resize-handle resize-handle-s" onmousedown="VGTDeskEngine.startResize(event, 'calendar', 's')"></div>
                    <div class="resize-handle resize-handle-e" onmousedown="VGTDeskEngine.startResize(event, 'calendar', 'e')"></div>
                    <div class="resize-handle resize-handle-w" onmousedown="VGTDeskEngine.startResize(event, 'calendar', 'w')"></div>
                    <div class="resize-handle resize-handle-nw" onmousedown="VGTDeskEngine.startResize(event, 'calendar', 'nw')"></div>
                    <div class="resize-handle resize-handle-ne" onmousedown="VGTDeskEngine.startResize(event, 'calendar', 'ne')"></div>
                    <div class="resize-handle resize-handle-sw" onmousedown="VGTDeskEngine.startResize(event, 'calendar', 'sw')"></div>
                    <div class="resize-handle resize-handle-se" onmousedown="VGTDeskEngine.startResize(event, 'calendar', 'se')"></div>

                    <!-- Titlebar -->
                    <div class="vgt-window-header cursor-move window-header">
                        <div class="vgt-window-dots">
                            <span class="vgt-window-dot dot-rose" onclick="VGTDeskEngine.closeWindow('calendar')"></span>
                            <span class="vgt-window-dot dot-amber" onclick="VGTDeskEngine.minimizeWindow('calendar')"></span>
                        </div>
                        <span class="vgt-window-title" id="calendar-title-accent">Kalender</span>
                        <div class="vgt-window-spacer" style="width: 30px;"></div>
                    </div>
                    
                    <!-- Body Content -->
                    <div class="vgt-window-body vgt-calendar-container" style="padding: 16px; background: #070a13; display: flex; flex-direction: column; height: calc(100% - 44px); box-sizing: border-box; overflow: hidden;">
                        <style nonce="<?php echo function_exists('vgt_get_csp_nonce') ? esc_attr(vgt_get_csp_nonce()) : ''; ?>">
                            .vgt-cal-header {
                                display: flex;
                                justify-content: space-between;
                                align-items: center;
                                margin-bottom: 12px;
                                flex-shrink: 0;
                            }
                            .vgt-cal-title {
                                font-size: 13px;
                                font-weight: 800;
                                color: #ffffff;
                                text-transform: uppercase;
                                letter-spacing: 0.05em;
                            }
                            .vgt-cal-btn {
                                background: rgba(255, 255, 255, 0.03);
                                border: 1px solid rgba(255, 255, 255, 0.06);
                                color: #cbd5e1;
                                padding: 4px 10px;
                                border-radius: 6px;
                                cursor: pointer;
                                transition: all 0.2s ease;
                                font-size: 11px;
                                font-weight: 700;
                            }
                            .vgt-cal-btn:hover {
                                background: var(--vgt-accent);
                                border-color: var(--vgt-accent);
                                color: #ffffff;
                                box-shadow: 0 0 10px var(--vgt-accent-rgba15);
                            }
                            .vgt-cal-weekdays {
                                display: grid;
                                grid-template-columns: repeat(7, 1fr);
                                text-align: center;
                                font-weight: 800;
                                font-size: 10px;
                                color: #64748b;
                                margin-bottom: 8px;
                                text-transform: uppercase;
                                letter-spacing: 0.05em;
                                flex-shrink: 0;
                            }
                            .vgt-cal-days {
                                display: grid;
                                grid-template-columns: repeat(7, 1fr);
                                gap: 4px;
                                flex-grow: 1;
                                align-content: space-between;
                            }
                            .vgt-cal-day {
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                aspect-ratio: 1 / 1;
                                border-radius: 8px;
                                font-size: 12px;
                                color: #cbd5e1;
                                cursor: pointer;
                                transition: all 0.15s ease;
                                border: 1px solid transparent;
                                font-weight: 600;
                            }
                            .vgt-cal-day:hover {
                                background: rgba(255, 255, 255, 0.05);
                                color: #ffffff;
                                transform: scale(1.05);
                            }
                            .vgt-cal-day.prev-next-month {
                                color: #334155;
                                font-weight: 500;
                            }
                            .vgt-cal-day.today {
                                background: var(--vgt-accent-rgba15);
                                border-color: var(--vgt-accent);
                                color: #ffffff;
                                font-weight: 800;
                                box-shadow: 0 0 8px var(--vgt-accent-rgba15);
                            }
                            .vgt-cal-day.selected {
                                background: var(--vgt-accent);
                                color: #ffffff;
                                font-weight: 700;
                                box-shadow: 0 4px 10px var(--vgt-accent-rgba15);
                            }
                        </style>
                        
                        <div class="vgt-cal-header">
                            <button class="vgt-cal-btn" id="vgt-cal-prev">&lt;</button>
                            <span class="vgt-cal-title" id="vgt-cal-month-year">Lade...</span>
                            <button class="vgt-cal-btn" id="vgt-cal-next">&gt;</button>
                        </div>
                        
                        <div class="vgt-cal-weekdays">
                            <div>Mo</div>
                            <div>Di</div>
                            <div>Mi</div>
                            <div>Do</div>
                            <div>Fr</div>
                            <div>Sa</div>
                            <div>So</div>
                        </div>
                        
                        <div class="vgt-cal-days" id="vgt-cal-days-grid"></div>
                        
                        <script nonce="<?php echo function_exists('vgt_get_csp_nonce') ? esc_attr(vgt_get_csp_nonce()) : ''; ?>">
                            document.addEventListener('DOMContentLoaded', () => {
                                const prevBtn = document.getElementById('vgt-cal-prev');
                                const nextBtn = document.getElementById('vgt-cal-next');
                                const monthYearLabel = document.getElementById('vgt-cal-month-year');
                                const daysGrid = document.getElementById('vgt-cal-days-grid');
                                
                                let currentDate = new Date();
                                
                                const renderCalendar = () => {
                                    const year = currentDate.getFullYear();
                                    const month = currentDate.getMonth();
                                    
                                    const firstDayOfMonth = new Date(year, month, 1);
                                    const lastDayOfMonth = new Date(year, month + 1, 0);
                                    
                                    // Get starting day index (0 = Sunday, 1 = Monday, etc.)
                                    // Convert to 0 = Monday, ..., 6 = Sunday
                                    let startDayIndex = firstDayOfMonth.getDay() - 1;
                                    if (startDayIndex < 0) startDayIndex = 6;
                                    
                                    const totalDays = lastDayOfMonth.getDate();
                                    const prevMonthLastDay = new Date(year, month, 0).getDate();
                                    
                                    const months = [
                                        'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 
                                        'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'
                                    ];
                                    
                                    monthYearLabel.textContent = `${months[month]} ${year}`;
                                    daysGrid.innerHTML = '';
                                    
                                    // Render previous month days
                                    for (let i = startDayIndex; i > 0; i--) {
                                        const dayDiv = document.createElement('div');
                                        dayDiv.classList.add('vgt-cal-day', 'prev-next-month');
                                        dayDiv.textContent = prevMonthLastDay - i + 1;
                                        daysGrid.appendChild(dayDiv);
                                    }
                                    
                                    // Render current month days
                                    const today = new Date();
                                    for (let i = 1; i <= totalDays; i++) {
                                        const dayDiv = document.createElement('div');
                                        dayDiv.classList.add('vgt-cal-day');
                                        dayDiv.textContent = i;
                                        
                                        if (year === today.getFullYear() && month === today.getMonth() && i === today.getDate()) {
                                            dayDiv.classList.add('today');
                                        }
                                        
                                        dayDiv.addEventListener('click', () => {
                                            const activeSelected = daysGrid.querySelector('.selected');
                                            if (activeSelected) activeSelected.classList.remove('selected');
                                            dayDiv.classList.add('selected');
                                        });
                                        
                                        daysGrid.appendChild(dayDiv);
                                    }
                                    
                                    // Render next month days to fill grid (42 grid slots = 6 rows * 7 columns)
                                    const totalSlots = 42;
                                    const currentSlots = startDayIndex + totalDays;
                                    const nextDays = totalSlots - currentSlots;
                                    for (let i = 1; i <= nextDays; i++) {
                                        const dayDiv = document.createElement('div');
                                        dayDiv.classList.add('vgt-cal-day', 'prev-next-month');
                                        dayDiv.textContent = i;
                                        daysGrid.appendChild(dayDiv);
                                    }
                                };
                                
                                prevBtn.addEventListener('click', () => {
                                    currentDate.setMonth(currentDate.getMonth() - 1);
                                    renderCalendar();
                                });
                                
                                nextBtn.addEventListener('click', () => {
                                    currentDate.setMonth(currentDate.getMonth() + 1);
                                    renderCalendar();
                                });
                                
                                renderCalendar();
                            });
                        </script>
                    </div>
                </div>
