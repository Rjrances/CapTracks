@extends('layouts.coordinator')

@section('title', 'Defense Calendar')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">
                    <i class="fas fa-calendar me-2"></i>
                    Defense Calendar
                </h2>
                <div>
                    <a href="{{ route('coordinator.defense.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>
                        Schedule Defense
                    </a>
                </div>
                         </div>
             
                                                       <!-- Calendar -->
                       <div class="calendar-container">
                          <div class="calendar-header">
                              <h2 class="calendar-title">
                                  @php
                                      $currentMonth = request('month', now()->month);
                                      $currentYear = request('year', now()->year);
                                      $date = \Carbon\Carbon::create($currentYear, $currentMonth, 1);
                                  @endphp
                                  {{ $date->format('F Y') }}
                              </h2>
                              <div class="calendar-nav">
                                  <button onclick="changeMonth(-1)" {{ $date->copy()->subMonth()->lt(now()->startOfYear()) ? 'disabled' : '' }}>
                                      <i class="fas fa-chevron-left"></i> Previous
                                  </button>
                                  <button onclick="goToToday()">Today</button>
                                  <button onclick="changeMonth(1)" {{ $date->copy()->addMonth()->gt(now()->endOfYear()->addYear()) ? 'disabled' : '' }}>
                                      Next <i class="fas fa-chevron-right"></i>
                                  </button>
                              </div>
                          </div>
                          
                                                     <table class="calendar-table">
                               <thead>
                                   <tr>
                                       <th>Sun</th>
                                       <th>Mon</th>
                                       <th>Tue</th>
                                       <th>Wed</th>
                                       <th>Thu</th>
                                       <th>Fri</th>
                                       <th>Sat</th>
                                   </tr>
                               </thead>
                               <tbody>
                                   @php
                                       $firstDay = $date->copy()->startOfMonth();
                                       $lastDay = $date->copy()->endOfMonth();
                                       $startDate = $firstDay->copy()->startOfWeek(\Carbon\Carbon::SUNDAY);
                                       $endDate = $lastDay->copy()->endOfWeek(\Carbon\Carbon::SATURDAY);
                                       $currentDate = $startDate->copy();
                                       $today = now()->startOfDay();
                                       $weekCount = 0;
                                   @endphp
                                   
                                   @while($currentDate <= $endDate)
                                       @if($weekCount % 7 == 0)
                                           <tr>
                                       @endif
                                       
                                       @php
                                           $isToday = $currentDate->eq($today);
                                           $isOtherMonth = $currentDate->month !== $currentMonth;
                                           $dayEvents = collect($calendarEvents)->filter(function($event) use ($currentDate) {
                                               return \Carbon\Carbon::parse($event['start'])->startOfDay()->eq($currentDate);
                                           });
                                       @endphp
                                       
                                       <td class="calendar-day {{ $isToday ? 'today' : '' }} {{ $isOtherMonth ? 'other-month' : '' }}">
                                           <div class="calendar-day-number">{{ $currentDate->day }}</div>
                                           
                                           @foreach($dayEvents as $event)
                                               <div class="calendar-event {{ $event['className'] ?? 'scheduled' }}" 
                                                    onclick="showEventDetails({{ json_encode($event) }})"
                                                    title="{{ $event['title'] }} - {{ $event['extendedProps']['group'] ?? 'N/A' }}">
                                                   {{ $event['title'] }}
                                               </div>
                                           @endforeach
                                       </td>
                                       
                                       @if($weekCount % 7 == 6)
                                           </tr>
                                       @endif
                                       
                                       @php
                                           $currentDate->addDay();
                                           $weekCount++;
                                       @endphp
                                   @endwhile
                                   
                                   @if($weekCount % 7 != 0)
                                       @for($i = 0; $i < (7 - ($weekCount % 7)); $i++)
                                           <td class="calendar-day other-month"></td>
                                       @endfor
                                       </tr>
                                   @endif
                               </tbody>
                           </table>
                       </div>
                   </div>
               </div>
         </div>
     </div>
 </div>

<!-- Defense Details Modal -->
<div class="modal fade" id="defenseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Defense Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="defenseModalBody">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="#" id="editDefenseBtn" class="btn btn-primary">
                    <i class="fas fa-edit me-2"></i>
                    Edit Defense
                </a>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
/* Google Calendar-inspired Design */
.calendar-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    overflow: hidden;
    font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    border: 1px solid #e8eaed;
    margin: 0;
    width: 100%;
    min-height: 80vh;
}

.calendar-header {
    background: white;
    padding: 16px 24px;
    color: #202124;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e8eaed;
}

.calendar-title {
    font-size: 22px;
    font-weight: 400;
    margin: 0;
    color: #202124;
}

.calendar-nav {
    display: flex;
    gap: 8px;
    align-items: center;
}

.calendar-nav button {
    background: white;
    color: #5f6368;
    border: 1px solid #dadce0;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s ease;
    min-width: 80px;
}

.calendar-nav button:hover {
    background: #f8f9fa;
    border-color: #c6c6c6;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.calendar-nav button:disabled {
    background: #f1f3f4;
    color: #9aa0a6;
    cursor: not-allowed;
    border-color: #f1f3f4;
}

.calendar-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    table-layout: fixed;
}

.calendar-table th {
    background: #f8f9fa;
    padding: 20px 12px;
    text-align: center;
    font-weight: 600;
    color: #5f6368;
    font-size: 14px;
    border-bottom: 2px solid #e8eaed;
    border-right: 1px solid #e8eaed;
    height: 60px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.calendar-table th:last-child {
    border-right: none;
}

.calendar-day {
    min-height: 150px;
    height: 150px;
    border-right: 1px solid #e8eaed;
    border-bottom: 1px solid #e8eaed;
    padding: 12px;
    position: relative;
    background: white;
    transition: background-color 0.2s ease;
    vertical-align: top;
    width: 14.28%;
}

.calendar-table td:last-child .calendar-day {
    border-right: none;
}

.calendar-day:hover {
    background: #f8f9fa;
}

.calendar-day.today {
    background: #e8f0fe;
}

.calendar-day.today .calendar-day-number {
    color: #1a73e8;
    font-weight: 500;
}

.calendar-day.other-month {
    background: #fafafa;
}

.calendar-day.other-month .calendar-day-number {
    color: #9aa0a6;
}

.calendar-day-number {
    font-weight: 600;
    color: #3c4043;
    margin-bottom: 12px;
    font-size: 18px;
    position: relative;
    z-index: 2;
    line-height: 1;
}

.calendar-event {
    background: #1a73e8;
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    margin: 4px 0;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    border: none;
    position: relative;
    z-index: 1;
    line-height: 1.3;
    max-width: 100%;
}

.calendar-event:hover {
    background: #1557b0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}

.calendar-event.approved {
    background: #34a853;
}

.calendar-event.approved:hover {
    background: #2d8a47;
}

.calendar-event.scheduled {
    background: #34a853;
    color: white;
}

.calendar-event.scheduled:hover {
    background: #2d8a47;
}

.calendar-event.pending {
    background: #9aa0a6;
}

.calendar-event.pending:hover {
    background: #80868b;
}

/* Responsive Design */
@media (max-width: 768px) {
    .calendar-header {
        flex-direction: column;
        gap: 12px;
        padding: 12px 16px;
    }
    
    .calendar-title {
        font-size: 18px;
    }
    
    .calendar-nav {
        width: 100%;
        justify-content: center;
    }
    
    .calendar-nav button {
        padding: 6px 12px;
        font-size: 12px;
        min-width: 60px;
    }
    
    .calendar-day {
        min-height: 80px;
        padding: 6px;
    }
    
    .calendar-day-number {
        font-size: 12px;
        margin-bottom: 6px;
    }
    
    .calendar-event {
        font-size: 10px;
        padding: 3px 6px;
        margin: 1px 0;
    }
    
    .calendar-table th {
        padding: 8px 4px;
        font-size: 10px;
        height: 32px;
    }
}

/* Animation for calendar events */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(4px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.calendar-event {
    animation: fadeInUp 0.2s ease-out;
}
</style>
@endpush

@push('scripts')
<script>
function changeMonth(direction) {
    const urlParams = new URLSearchParams(window.location.search);
    let month = parseInt(urlParams.get('month')) || new Date().getMonth() + 1;
    let year = parseInt(urlParams.get('year')) || new Date().getFullYear();
    
    month += direction;
    
    if (month > 12) {
        month = 1;
        year++;
    } else if (month < 1) {
        month = 12;
        year--;
    }
    
    urlParams.set('month', month);
    urlParams.set('year', year);
    window.location.search = urlParams.toString();
}

function goToToday() {
    const today = new Date();
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('month', today.getMonth() + 1);
    urlParams.set('year', today.getFullYear());
    window.location.search = urlParams.toString();
}

function showEventDetails(event) {
    // Show defense details modal
    const modal = new bootstrap.Modal(document.getElementById('defenseModal'));
    
    // Create event details HTML
    const eventDetails = `
        <div class="row">
            <div class="col-md-6">
                <h6>Defense Details</h6>
                                 <p><strong>Defense Type:</strong> ${event.extendedProps.defenseType || 'N/A'}</p>
                <p><strong>Group:</strong> ${event.extendedProps.group || 'N/A'}</p>
                <p><strong>Adviser:</strong> ${event.extendedProps.adviser || 'N/A'}</p>
                <p><strong>Status:</strong> <span class="badge bg-${event.extendedProps.status === 'approved' ? 'success' : event.extendedProps.status === 'scheduled' ? 'success' : 'secondary'}">${event.extendedProps.status.charAt(0).toUpperCase() + event.extendedProps.status.slice(1)}</span></p>
            </div>
            <div class="col-md-6">
                <h6>Schedule</h6>
                <p><strong>Date:</strong> ${new Date(event.start).toLocaleDateString()}</p>
                <p><strong>Time:</strong> ${event.extendedProps.time || new Date(event.start).toLocaleTimeString()}</p>
                <p><strong>Location:</strong> ${event.extendedProps.room || 'TBA'}</p>
            </div>
        </div>
    `;
    
    document.getElementById('defenseModalBody').innerHTML = eventDetails;
    document.getElementById('editDefenseBtn').href = `/coordinator/defense/${event.id}/edit`;
    modal.show();
}
</script>
@endpush
