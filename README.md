# bricks-comments-addon

It just adds a toggle control to the Bricks comments element in order to be able to push the comment form in front
of the comments list.

![image](assets/screenshot-bricks-editor.jpg)

## Technical stuff

To archive this the plugin simply determins if the box is checked. If so, it registers two files (a JS and a CSS).

### JS

Nothing special here. Just wrapping the `.comments-title` and `.comment-list` with `<div
class="comments__wrapper"></div>`. This automatically bringt up the for in front of the comments listing.

### CSS

Just giving the `.comments__wrapper` a little `margin-top: 40px`