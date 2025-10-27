jQuery(document).ready(function($) {
    'use strict';

    // Audio upload functionality
    let audioFrame;

    $('.sfmp-upload-audio').on('click', function(e) {
        e.preventDefault();

        // If the media frame already exists, reopen it.
        if (audioFrame) {
            audioFrame.open();
            return;
        }

        // Create a new media frame
        audioFrame = wp.media({
            title: sfmp_admin.title,
            button: {
                text: sfmp_admin.button_text
            },
            library: {
                type: 'audio'
            },
            multiple: false
        });

        // When an audio file is selected, run a callback.
        audioFrame.on('select', function() {
            const attachment = audioFrame.state().get('selection').first().toJSON();
            
            // Set the audio ID and update preview
            $('#sfmp_case_audio_id').val(attachment.id);
            
            // Update preview
            const audioPreview = $('.sfmp-audio-preview');
            audioPreview.html(`
                <audio controls style="width: 100%;">
                    <source src="${attachment.url}" type="audio/mpeg">
                    Your browser does not support the audio element.
                </audio>
                <p class="description">${attachment.filename}</p>
            `);
            
            // Show remove button
            if (!$('.sfmp-remove-audio').length) {
                $('.sfmp-upload-audio').after('<button type="button" class="button sfmp-remove-audio">Remove Audio</button>');
            }
        });

        // Finally, open the modal on click
        audioFrame.open();
    });

    // Remove audio file
    $(document).on('click', '.sfmp-remove-audio', function(e) {
        e.preventDefault();
        
        $('#sfmp_case_audio_id').val('');
        $('.sfmp-audio-preview').html('<p>No audio file selected.</p>');
        $(this).remove();
    });

    // Experience group change handler
    $('#sfmp_experience_group').on('change', function() {
        const selected = $(this).val();
        console.log('Experience group changed to:', selected);
    });

    // Free access toggle handler
    $('#sfmp_case_is_free').on('change', function() {
        const isFree = $(this).is(':checked');
        console.log('Free access:', isFree);
    });

    // Topic selection counter
    function updateTopicCount() {
        const checked = $('input[name="sfmp_case_topics[]"]:checked').length;
        console.log('Topics selected:', checked);
    }

    $('input[name="sfmp_case_topics[]"]').on('change', updateTopicCount);
    
    // Initial count
    updateTopicCount();
});