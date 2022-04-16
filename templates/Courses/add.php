<?php
use Cake\Core\Configure;
?>
<div class="row">
    <p></p>
    <h2><span class="glyphicon glyphicon-plus"></span>&nbsp;&nbsp;&nbsp;Add Course</h2>
    <div class="column-responsive column-80">
        Please provide the meta data in <b><u>English</u></b>, independent from the language the course is held in.
        <p>&nbsp;</p>
        <div class="courses form content">
            <?= $this->Form->create($course) ?>
            <fieldset>
                <legend><?= __('Add Course') ?></legend>
                <?php
                echo $this->Form->control('name', ['label' => 'Course Name*']);
                echo $this->Form->control('online_course');
                echo $this->Form->control('course_type_id', ['label' => 'Education Type*', 'options' => $course_types,  'empty' => true]);
                echo $this->Form->control('language_id', ['label' => 'Language*', 'options' => $languages, 'empty' => true]);
                echo $this->Form->control('ects', ['label' => 'ECTS']);
                ?>
                Credit points rewarded within the European Credit Transfer and Accumulation System (ECTS). <br>Leave blank if not applicable.
                <p>&nbsp;</p>
                <?php
                echo $this->Form->control('info_url', ['label' => 'Source URL*']);
                echo 'The public web address of the course description and syllabus.<p>&nbsp;</p>';
                echo $this->Form->control('contact_name', ['label' => 'Lecturer Name']);
                echo $this->Form->control('contact_mail', ['label' => 'Lecturer E-Mail']);
                echo $this->Form->control('description', ['label' => 'Description']);
                echo $this->Form->control('access_requirements', ['label' => 'Access Requirements']);
                ?>
                <p>&nbsp;</p>
                <p>For <strong>recurring start dates</strong>, please enter all dates over one full year, when students can start the course. 
                These dates only consist of [month]-[day] and are meant to be valid in subsequent years.</p>
                <p><strong><u>One-off start dates</u></strong> consist of a full date 
                [year]-[month]-[day] and invalidate the course entry after their expiry. Multiple dates can be separated by semicolon.<br>
                The course will disappear from the list after the last one-off date has expired.</p>
                <?php
                echo $this->Form->control('start_date', ['label' => 'Start Date*']);
                echo $this->Form->control('recurring', ['label' => 'Recurring']);
                echo $this->Form->control('duration', ['label' => 'Duration*']);
                echo $this->Form->control('course_duration_unit_id', ['label' => 'Duration type*', 'options' => $course_duration_units, 'empty' => true]);
                echo $this->Form->control('institution_id', ['label' => 'Institution*', 'options' => $institutions, 'default' => $user->institution_id]);
                // update map when an institution is selected
                ?>
                <script>
                    var institutionSelector = document.getElementById("institution-id");
                    var institutionId = null;
                    var institutionsLocations = <?php echo json_encode($institutionsLocations); ?>

                    institutionSelector.addEventListener(
                        'change',
                        function() {
                            map.setCenter([
                                institutionsLocations[institutionSelector.value]['lon'], 
                                institutionsLocations[institutionSelector.value]['lat']
                            ]);
                            map.setZoom(9);
                            marker.setLngLat([
                                institutionsLocations[institutionSelector.value]['lon'], 
                                institutionsLocations[institutionSelector.value]['lat']
                            ]);
                        }
                    );
                </script>
                <?php                
                echo $this->Form->control('department', ['label' => 'Department*']);
                // TODO: change to hidden
                echo $this->Form->control('lon', ['default' => $userInstitution->lon]);
                echo $this->Form->control('lat', ['default' => $userInstitution->lat]);
                //  <p>&nbsp;</p>
                ?>
                <b>Location</b><br>
                 Coordinates can be drawn in from the institution selector above. If not applicable, adjust using the location picker.<br>
                 Changing your selection from the institutions list above will overwrite the current coordinate value.<br>
                 -You can zoom using the scroll wheel.<br>
                 -You can move the map, by dragging with the mouse.<br>
                 -Place the blue marker on the correct position, to confirm the location.
                <p></p>
                <?php
                echo $this->Html->script('https://api.mapbox.com/mapbox-gl-js/v2.8.0/mapbox-gl.js');
                echo $this->Html->css('https://api.mapbox.com/mapbox-gl-js/v2.8.0/mapbox-gl.css');
                ?>
                <div id='map' style='width: 600px; height: 450px;'></div>
                <script>
                    mapboxgl.accessToken = '<?= Configure::read('map.apiKey') ?>';
                    const map = new mapboxgl.Map({
                        container: 'map', // container ID
                        style: 'mapbox://styles/mapbox/streets-v11', // style URL
                        center: [<?= $userInstitution->lon ?>, <?= $userInstitution->lat ?>], // starting position [lng, lat]
                        zoom: 9 // starting zoom
                        });
                    // add zoom controls to the map
                    map.addControl(new mapboxgl.NavigationControl());
                    // add draggable marker (blue)
                    const marker = new mapboxgl.Marker({
                        draggable: true
                        })
                        .setLngLat([<?= $userInstitution->lon ?>, <?= $userInstitution->lat ?>])
                        .addTo(map);
                    function onDragEnd() {
                        // update hidden form fields with the location selected by user
                        const lngLat = marker.getLngLat();
                        document.getElementById('lon').value = lngLat.lng;
                        document.getElementById('lat').value = lngLat.lat;
                    }
                    marker.on('dragend', onDragEnd);
                </script>
                <p></p>
                <?php
                echo $this->Form->control('courses_disciplines', ['label' => 'Disciplines*', 'options' => $disciplines, 'multiple' => 'multiple']);
                echo $this->Form->control('courses_tadirah_techniques', ['label' => 'Tadirah Techniques*', 'options' => $tadirah_techniques, 'multiple' => 'multiple']);
                echo $this->Form->control('courses_tadirah_objects', ['label' => 'Tadirah Objects*', 'options' => $tadirah_objects, 'multiple' => 'multiple']);
                ?>
                <p>&nbsp;
                <?= $this->Form->control('active', ['label' => 'Show course in the registry']); ?>
                Check this box if your course is ready to be published in the registry.<br>
                When unchecked, you will be able to complete the course description later.
                </p>
            </fieldset>
            <p>&nbsp;</p>
            <?= $this->Form->button(__('Add Course')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>