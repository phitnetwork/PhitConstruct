<div>
    @if (isset($activeTimeEntry) && $activeTimeEntry)
        <div style='display: flex; align-items: center; margin-left: 20px;'>
            <span style='font-weight: bold; margin-right: 10px;'>{{ $activeTimeEntry->description }}</span>
            <span id='timer' data-diff='{{ $diffInSeconds }}' style='margin-right: 10px;'>{{ $formattedDuration }}</span>            
            <x-filament::icon-button
                icon="heroicon-s-stop-circle"
                wire:click="openNewUserModal"
                color="danger"
                wire:click='stopTimer'
                label="Stop Timer"
                size="xl"
            />
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const timerElement = document.getElementById('timer');
                if (timerElement) {
                    let diff = parseInt(timerElement.getAttribute('data-diff'), 10);
    
                    function updateTimer() {
                        diff++;
                        const hours = Math.floor(diff / 3600);
                        const minutes = Math.floor((diff % 3600) / 60);
                        const seconds = diff % 60;
    
                        timerElement.textContent = 
                            String(hours).padStart(2, '0') + ':' + 
                            String(minutes).padStart(2, '0') + ':' + 
                            String(seconds).padStart(2, '0');
                    }
    
                    setInterval(updateTimer, 1000);
                }
            });
        </script>
    @endif
</div>