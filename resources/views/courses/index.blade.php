<table class="table">
    <thead>
        <tr>
            <th>#</th>
            <th>Course Name</th>
            <th>Description</th>
        </tr>
    </thead>
    <tbody>
        @foreach($courses as $course)
            <tr>
                <td>{{ $course->carrer->name}}</td>
                <td>{{ $course->carrer_id}}</td>
            </tr>
        @endforeach
    </tbody>
</table>