# TableHero

**[WIP]** HTML table based version of GuitarHero.

## How to run

Simply run a server like this:

`php -S localhost`

and go to localhost on the browser.

Refresh when you want.

## How to play

The songs must follow the format used by **FretsOnFire**: go search 'em and unzip 'em inside the `songs` directory (one subdirectory per song).

The home page shows a list of the available songs and available instruments.

Keys:

* Guitar keys: `1`, `2`, `3`, `4`, `5`;
* Pick: `Enter` or `Spacebar`.

## How it works

Every song directory must have:
* a `.ogg` file, which is the song that will be played;
* a `.mid` file, which is the track used to create notes for the game.

At the beginning the `.mid` is parsed and a big table is created to store all notes and notes-tails (basically, the `hammer on`).
Every row of this table is one tenth of a second, give or take.
This big table then is set in position absolute and starts to move via css in a linear way; the duration is exactly the song duration.

As the table is too big to load inside the DOM, what I do is:
* render it once;
* do some math (table height);
* leave inside the table only the visible rows;
* start moving the table;
* remove a row whenever it's not visible anymore;
* add a new visible row whenever an old row disappear.

Still, it doesn't work, but hey: it was fun!

## Notes

To parse the midi clientside, I used [MidiConvert.js](https://github.com/Tonejs/Midi).