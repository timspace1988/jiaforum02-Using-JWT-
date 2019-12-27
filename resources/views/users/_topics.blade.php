@if(count($topics))

  <ul class="list-group mt-4 border-0">
    @foreach($topics as $topic)
      <li class="list-group-item pl-2 pr-2 border-right-0 border-left-0 @if($loop->first) border-top-0 @endif">
        <a href="{{ route('topics.show', $topic->id) }}">
          {{ $topic->title }}
        </a>
        <span class="meta float-right text-secondary">
          {{$topic->reply_count}} replies
          <span> . </span>
          {{ $topic->created_at->diffForHumans() }}
        </span>
      </li>
    @endforeach
  </ul>
@else
  <div class="empty-block">There is no post. ~_~</div>
@endif

{{-- Page navigation bar --}}
<div class="mt-4 pt-1">
  {!! $topics->render() !!}
</div>
