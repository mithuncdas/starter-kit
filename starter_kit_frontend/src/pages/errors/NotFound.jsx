import { SearchRemoveIcon } from "@hugeicons/core-free-icons"
import ErrorPage from "./ErrorPage"

export default function NotFound() {
  return (
    <ErrorPage
      code={404}
      icon={SearchRemoveIcon}
      title="Page not found"
      description="The page you're looking for doesn't exist or has been moved."
    />
  )
}
