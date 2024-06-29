// Wrap comment list and title with a wrapper div with the class name "comments__wrapper"
// This automatically sets the form in front of the comment list

const commentInner = document.querySelector(".bricks-comments-inner");
const commentTitle = document.querySelector(".comments-title");
const commentList = document.querySelector(".comment-list");
const commentWrapper = document.createElement("div");

commentWrapper.setAttribute("class", "comments__wrapper");
commentWrapper.appendChild(commentTitle);
commentWrapper.appendChild(commentList);
commentInner.appendChild(commentWrapper);