## Alive 

This is a Polyfony bundle, that serves `/alive` url 
This url will return "OK" with status 200 if everything is ok, 
or ["error"=>"..."] with non 200 status if any of : 
- database
- filesystem

is unavailable
