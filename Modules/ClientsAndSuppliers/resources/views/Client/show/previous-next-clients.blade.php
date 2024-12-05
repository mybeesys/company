<div class="row">

    <div class="col-2">
        @if ($previous)
            <a href="{{ route('client-show', $previous->id) }}" class="btn btn-primary "
                style="padding: 5px;
                border-radius: 50%;"><i
                    @if (app()->getLocale() == 'en') class="ki-outline ki-arrow-left fs-1 p-0" @endif
                    @if (app()->getLocale() == 'ar') class="ki-outline ki-arrow-right fs-1 p-0" @endif></i></a>
        @endif
    </div>
    <div class="col-8">

        <select id="contacts_list" class="form-select form-select-solid select-2" name="id">

            @foreach ($clients as $_client)
                <option value="{{ $_client->id }}" @if ($contact->id == $_client->id) selected @endif>

                    {{ $_client->name }}
                </option>
            @endforeach

        </select>
    </div>
    <div class="col-2">

        @if ($next)
            <a href="{{ route('client-show', $next->id) }}" class="btn btn-primary"
                style="padding: 5px;
                border-radius: 50%;"><i
                    @if (app()->getLocale() == 'en') class="ki-outline ki-arrow-right fs-1 p-0" @endif
                    @if (app()->getLocale() == 'ar') class="ki-outline ki-arrow-left fs-1 p-0" @endif></i></a>
        @endif
    </div>
</div>
