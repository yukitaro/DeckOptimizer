<!DOCTYPE html>
<html>
    <head>
        <title>Kendrick's Cards</title>
        @vite(['resources/js/app.js'])
    </head>
    <body>
<!--         <table>
            <tr>
                <th scope="col">Card Name</th>
                <th scope="col">Set Name</th>
                <th scope="col">Type</th>
                <th scope="col">Mana Cost</th>
                <th scope="col">Colors</th>
                <th scope="col">Text</th>
            </tr>
            @foreach ($matchingCards as $card)
            <tr>
                <td><a href="{{$card->image_url}}">{{ $card->name }}</a></td>
                <td>{{ $card->set_name }}</td>
                <td>{{ $card->type }}</td>
                <td>{{ $card->mana_cost }}</td>
                <td>{{ $card->colors }}</td>
                <td>{{ $card->text }}</td>
            </tr>
            @endforeach
        </table> -->
        <div id="app">
            <br><br>Wait..<br><br>
            <card-listing></card-listing>
        </div>
        <div id="deckApp">
            <card-listing-vuetify></card-listing-vuetify>
        </div>
    </body>
</html>
